<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

class KmhConverterService
{
    public function __construct(
        protected DatabaseBackupService $backupService
    ) {}

    /**
     * Periksa metadata dan daftar tabel dari berkas .kmh yang diunggah dari PC.
     * Menyimpan berkas sementara dengan token aman agar saat klik konversi tidak perlu upload ulang 2x.
     *
     * @return array{
     *     token: string,
     *     filename: string,
     *     size_human: string,
     *     created_at_human: string,
     *     created_at_diff: string,
     *     table_count: int,
     *     tables: array<string>,
     *     database: string,
     *     has_media: bool
     * }
     */
    public function inspectUploadedFile(UploadedFile $file): array
    {
        $realPath = $file->getRealPath();
        if (!$realPath || !is_file($realPath)) {
            throw new RuntimeException("Berkas unggahan tidak ditemukan atau korup.");
        }

        $kmhContent = file_get_contents($realPath);
        if ($kmhContent === false) {
            throw new RuntimeException("Gagal membaca berkas .kmh yang diunggah.");
        }

        // Dekripsi arsip .kmh menggunakan kunci sistem untuk verifikasi & inspeksi
        $decryptedZipContent = $this->backupService->decryptFromKmh($kmhContent);
        unset($kmhContent);

        $tempZipPath = storage_path('app/temp_inspect_' . uniqid() . '.zip');
        file_put_contents($tempZipPath, $decryptedZipContent);
        unset($decryptedZipContent);

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath) !== true) {
            @unlink($tempZipPath);
            throw new RuntimeException("Konten terdekripsi bukan merupakan arsip ZIP yang sah.");
        }

        try {
            $manifestJson = $zip->getFromName('manifest.json');
            $manifest = $manifestJson ? json_decode($manifestJson, true) : [];

            $tables = $manifest['tables'] ?? [];

            // Jika manifest tidak memiliki daftar tabel, baca cepat dari struktur database.sql
            if (empty($tables)) {
                $sqlContent = $zip->getFromName('database.sql');
                if ($sqlContent) {
                    preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i', $sqlContent, $matches);
                    $tables = array_values(array_unique($matches[1] ?? []));
                }
            }

            sort($tables);

            $createdAt = isset($manifest['created_at']) 
                ? Carbon::parse($manifest['created_at']) 
                : Carbon::createFromTimestamp($file->getMTime());

            // Simpan cache berkas sementara dengan token aman untuk mencegah upload ulang ganda
            $tempDir = storage_path('app/temp_converter');
            File::ensureDirectoryExists($tempDir);

            // Bersihkan file sementara yang lebih tua dari 30 menit
            foreach (File::files($tempDir) as $f) {
                if ((time() - $f->getMTime()) > 1800) {
                    @unlink($f->getPathname());
                }
            }

            $token = bin2hex(random_bytes(16));
            $cachedKmhPath = $tempDir . '/' . $token . '.kmh';
            copy($realPath, $cachedKmhPath);

            return [
                'token' => $token,
                'filename' => $file->getClientOriginalName(),
                'size_human' => $this->backupService->formatBytes($file->getSize()),
                'created_at_human' => $createdAt->translatedFormat('d F Y, H:i') . ' WIB',
                'created_at_diff' => $createdAt->diffForHumans(),
                'table_count' => count($tables),
                'tables' => $tables,
                'database' => $manifest['database'] ?? 'rkm_kmh',
                'has_media' => (bool) ($manifest['has_media'] ?? false),
            ];
        } finally {
            $zip->close();
            if (is_file($tempZipPath)) {
                @unlink($tempZipPath);
            }
        }
    }

    /**
     * Konversi berkas .kmh menggunakan token sesi yang sudah diunggah sebelumnya.
     */
    public function convertByToken(string $token, array $selectedTables, string $format): array
    {
        $tempDir = storage_path('app/temp_converter');
        $cleanToken = preg_replace('/[^a-zA-Z0-9]/', '', $token);
        $cachedPath = $tempDir . '/' . $cleanToken . '.kmh';

        if (!is_file($cachedPath)) {
            throw new RuntimeException("Sesi berkas telah kadaluarsa atau tidak ditemukan di server. Silakan unggah kembali berkas .kmh Anda.");
        }

        try {
            return $this->convertKmhFilePath($cachedPath, 'arsip.kmh', $selectedTables, $format);
        } finally {
            @unlink($cachedPath);
        }
    }

    /**
     * Konversi berkas .kmh langsung dari UploadedFile.
     */
    public function convertUploadedFile(UploadedFile $file, array $selectedTables, string $format): array
    {
        $realPath = $file->getRealPath();
        if (!$realPath || !is_file($realPath)) {
            throw new RuntimeException("Berkas unggahan tidak ditemukan.");
        }

        return $this->convertKmhFilePath($realPath, $file->getClientOriginalName(), $selectedTables, $format);
    }

    /**
     * Method inti konversi dari file path .kmh ke format tujuan (xlsx, csv, sql, media).
     *
     * @param string $kmhFilePath
     * @param string $originalFilename
     * @param array<string> $selectedTables
     * @param string $format 'xlsx' | 'csv' | 'sql' | 'media'
     * @return array{
     *     path: string,
     *     filename: string,
     *     mime: string,
     *     work_dir: string
     * }
     */
    public function convertKmhFilePath(string $kmhFilePath, string $originalFilename, array $selectedTables, string $format): array
    {
        $format = strtolower($format);
        if (!in_array($format, ['xlsx', 'csv', 'sql', 'media'], true)) {
            throw new RuntimeException("Format konversi tidak didukung: {$format}");
        }

        if ($format !== 'media' && empty($selectedTables)) {
            throw new RuntimeException("Silakan pilih minimal 1 tabel untuk dikonversi.");
        }

        $workDir = storage_path('app/temp_kmh_conv_' . uniqid());
        File::ensureDirectoryExists($workDir);

        $tempZipPath = $workDir . '/package.zip';
        $sqlPath = $workDir . '/database.sql';

        try {
            // 1. Dekripsi payload .kmh
            $kmhContent = file_get_contents($kmhFilePath);
            if ($kmhContent === false) {
                throw new RuntimeException("Gagal membaca berkas .kmh untuk konversi.");
            }

            $decryptedZipContent = $this->backupService->decryptFromKmh($kmhContent);
            unset($kmhContent);

            file_put_contents($tempZipPath, $decryptedZipContent);
            unset($decryptedZipContent);

            $timestamp = now()->format('Ymd_His');

            // Format KHUSUS: Ekstraksi Folder Media/Foto Asli
            if ($format === 'media') {
                $outputFilename = 'Arsip_KMH_Media_Foto_' . $timestamp . '.zip';
                $outputPath = $workDir . '/' . $outputFilename;

                $zipIn = new ZipArchive();
                if ($zipIn->open($tempZipPath) !== true) {
                    throw new RuntimeException("Gagal membuka arsip kontainer untuk ekstraksi media.");
                }

                $zipOut = new ZipArchive();
                if ($zipOut->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    $zipIn->close();
                    throw new RuntimeException("Gagal membuat arsip ZIP untuk media.");
                }

                $mediaCount = 0;
                for ($i = 0; $i < $zipIn->numFiles; $i++) {
                    $stat = $zipIn->statIndex($i);
                    $name = $stat['name'];
                    if (str_starts_with($name, 'storage/') && !str_ends_with($name, '/')) {
                        $content = $zipIn->getFromIndex($i);
                        $relativeName = substr($name, 8); // buang prefix 'storage/'
                        $zipOut->addFromString($relativeName, $content);
                        $mediaCount++;
                    }
                }
                $zipIn->close();
                $zipOut->close();
                @unlink($tempZipPath);

                if ($mediaCount === 0) {
                    throw new RuntimeException("Berkas arsip .kmh ini tidak memiliki lampiran media foto fisik.");
                }

                return [
                    'path' => $outputPath,
                    'filename' => $outputFilename,
                    'mime' => 'application/zip',
                    'work_dir' => $workDir,
                ];
            }

            // 2. Ekstrak database.sql saja
            $zip = new ZipArchive();
            if ($zip->open($tempZipPath) !== true) {
                throw new RuntimeException("Gagal membuka arsip kontainer backup.");
            }

            $zip->extractTo($workDir, ['database.sql']);
            $zip->close();
            @unlink($tempZipPath);

            if (!is_file($sqlPath)) {
                throw new RuntimeException("Berkas database.sql tidak ditemukan di dalam arsip .kmh.");
            }

            // 3. Proses berdasarkan format SQL
            if ($format === 'sql') {
                $outputFilename = 'Arsip_KMH_SQL_' . $timestamp . '.sql';
                $outputPath = $workDir . '/' . $outputFilename;
                $this->extractFilteredSql($sqlPath, $selectedTables, $outputPath);
                @unlink($sqlPath);

                return [
                    'path' => $outputPath,
                    'filename' => $outputFilename,
                    'mime' => 'application/sql',
                    'work_dir' => $workDir,
                ];
            }

            // Ekstrak data tabel ke bentuk CSV sementara di disk (Memory-Safe Streaming)
            $csvDir = $workDir . '/csv_tables';
            File::ensureDirectoryExists($csvDir);
            $extractedTables = $this->streamSqlToCsvFiles($sqlPath, $selectedTables, $csvDir);
            @unlink($sqlPath);

            // Format CSV
            if ($format === 'csv') {
                $outputFilename = 'Arsip_KMH_CSV_' . $timestamp . '.zip';
                $outputPath = $workDir . '/' . $outputFilename;

                $zipOut = new ZipArchive();
                if ($zipOut->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    throw new RuntimeException("Gagal membuat arsip ZIP untuk file CSV.");
                }

                foreach ($extractedTables as $tbl => $csvFile) {
                    if (is_file($csvFile)) {
                        $zipOut->addFile($csvFile, $tbl . '.csv');
                    }
                }
                $zipOut->close();
                File::deleteDirectory($csvDir);

                return [
                    'path' => $outputPath,
                    'filename' => $outputFilename,
                    'mime' => 'application/zip',
                    'work_dir' => $workDir,
                ];
            }

            // Format XLSX
            if (count($selectedTables) === 1) {
                // Single table -> langsung 1 file .xlsx
                $tbl = $selectedTables[0];
                $outputFilename = 'Tabel_' . $tbl . '_' . $timestamp . '.xlsx';
                $outputPath = $workDir . '/' . $outputFilename;

                $csvFile = $csvDir . '/' . $tbl . '.csv';
                $this->convertCsvToXlsx($csvFile, $tbl, $outputPath);
                File::deleteDirectory($csvDir);

                return [
                    'path' => $outputPath,
                    'filename' => $outputFilename,
                    'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'work_dir' => $workDir,
                ];
            } else {
                // Multiple tables -> dikemas dalam 1 file .zip berisi masing-masing .xlsx
                $outputFilename = 'Arsip_KMH_Excel_' . $timestamp . '.zip';
                $outputPath = $workDir . '/' . $outputFilename;

                $zipOut = new ZipArchive();
                if ($zipOut->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    throw new RuntimeException("Gagal membuat paket ZIP Excel.");
                }

                foreach ($extractedTables as $tbl => $csvFile) {
                    if (is_file($csvFile)) {
                        $tblXlsxPath = $workDir . '/' . $tbl . '.xlsx';
                        $this->convertCsvToXlsx($csvFile, $tbl, $tblXlsxPath);
                        $zipOut->addFile($tblXlsxPath, $tbl . '.xlsx');
                    }
                }
                $zipOut->close();
                File::deleteDirectory($csvDir);

                return [
                    'path' => $outputPath,
                    'filename' => $outputFilename,
                    'mime' => 'application/zip',
                    'work_dir' => $workDir,
                ];
            }
        } catch (\Throwable $e) {
            $this->cleanDirectory($workDir);
            throw $e;
        }
    }

    /**
     * Memfilter isi database.sql hanya untuk tabel terpilih dan menulis ke file output SQL.
     */
    protected function extractFilteredSql(string $inputSqlPath, array $selectedTables, string $outputSqlPath): void
    {
        $inHandle = fopen($inputSqlPath, 'r');
        $outHandle = fopen($outputSqlPath, 'w');

        if (!$inHandle || !$outHandle) {
            if ($inHandle) fclose($inHandle);
            if ($outHandle) fclose($outHandle);
            throw new RuntimeException("Gagal membuka berkas SQL untuk operasi filter.");
        }

        try {
            $nowStr = now()->format('Y-m-d H:i:s');
            $tablesStr = implode(', ', $selectedTables);
            fwrite($outHandle, "-- ==============================================================\n");
            fwrite($outHandle, "-- RKM-KMH SECURE DATABASE DUMP - HASIL FILTER KONVERSI\n");
            fwrite($outHandle, "-- Waktu Ekstrak : {$nowStr} WIB\n");
            fwrite($outHandle, "-- Tabel Terpilih: {$tablesStr}\n");
            fwrite($outHandle, "-- ==============================================================\n\n");
            fwrite($outHandle, "SET NAMES utf8mb4;\n");
            fwrite($outHandle, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

            $selectedMap = array_fill_keys($selectedTables, true);
            $currentTable = null;
            $writing = false;

            while (($line = fgets($inHandle)) !== false) {
                // Deteksi nama tabel pada komentar, DROP TABLE, CREATE TABLE, atau INSERT INTO
                if (preg_match('/(?:DROP\s+TABLE\s+IF\s+EXISTS|CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?|INSERT\s+INTO)\s+`?([a-zA-Z0-9_]+)`?/i', $line, $m)) {
                    $tbl = $m[1];
                    $currentTable = $tbl;
                    $writing = isset($selectedMap[$tbl]);
                } elseif (preg_match('/^--\s+(?:Struktur|Dumping\s+Data)\s+untuk\s+`?([a-zA-Z0-9_]+)`?/i', $line, $m)) {
                    $tbl = $m[1];
                    $currentTable = $tbl;
                    $writing = isset($selectedMap[$tbl]);
                }

                if ($writing) {
                    fwrite($outHandle, $line);
                }
            }

            fwrite($outHandle, "\nSET FOREIGN_KEY_CHECKS = 1;\n");
            fwrite($outHandle, "-- END OF FILTERED DUMP\n");
        } finally {
            fclose($inHandle);
            fclose($outHandle);
        }
    }

    /**
     * Membaca stream SQL dan mengekstrak tabel-tabel yang dipilih ke file CSV terpisah.
     * Sangat hemat memori (RAM usage < 5MB).
     *
     * @return array<string, string> Map tableName => csvFilePath
     */
    protected function streamSqlToCsvFiles(string $sqlPath, array $selectedTables, string $csvOutputDir): array
    {
        $handle = fopen($sqlPath, 'r');
        if (!$handle) {
            throw new RuntimeException("Gagal membuka file database.sql untuk pembacaan stream.");
        }

        $selectedMap = array_fill_keys($selectedTables, true);
        $csvHandles = [];
        $headerWritten = [];
        $activeTable = null;
        $activeColumns = [];

        try {
            while (($line = fgets($handle)) !== false) {
                $trimmed = trim($line);
                if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
                    continue;
                }

                // Cek baris INSERT INTO `tableName` (`col1`, `col2`, ...) VALUES
                if (preg_match('/^INSERT\s+INTO\s+`?([a-zA-Z0-9_]+)`?\s*(?:\(([^)]+)\))?\s*VALUES/i', $trimmed, $m)) {
                    $tbl = $m[1];
                    if (isset($selectedMap[$tbl])) {
                        $activeTable = $tbl;
                        if (!isset($csvHandles[$tbl])) {
                            $csvPath = $csvOutputDir . '/' . $tbl . '.csv';
                            $csvHandles[$tbl] = fopen($csvPath, 'w');
                            // Tulis UTF-8 BOM agar Excel membukanya dengan karakter yang sempurna
                            fwrite($csvHandles[$tbl], "\xEF\xBB\xBF");
                        }

                        if (!empty($m[2]) && empty($headerWritten[$tbl])) {
                            preg_match_all('/`?([a-zA-Z0-9_]+)`?/', $m[2], $colMatches);
                            $activeColumns = array_values(array_filter(array_map('trim', $colMatches[1] ?? [])));
                            if (!empty($activeColumns)) {
                                fputcsv($csvHandles[$tbl], $activeColumns);
                                $headerWritten[$tbl] = true;
                            }
                        }
                    } else {
                        $activeTable = null;
                    }
                    continue;
                }

                // Jika sedang membaca baris data dari activeTable
                if ($activeTable && isset($csvHandles[$activeTable])) {
                    $tuples = $this->extractTuplesFromSqlLine($trimmed);
                    foreach ($tuples as $tupleStr) {
                        $values = $this->parseTupleValues($tupleStr);
                        fputcsv($csvHandles[$activeTable], $values);
                    }

                    if (str_ends_with($trimmed, ';')) {
                        $activeTable = null;
                    }
                }
            }

            // Pastikan semua tabel terpilih memiliki file CSV, meskipun kosong
            foreach ($selectedTables as $tbl) {
                if (!isset($csvHandles[$tbl])) {
                    $csvPath = $csvOutputDir . '/' . $tbl . '.csv';
                    $h = fopen($csvPath, 'w');
                    fwrite($h, "\xEF\xBB\xBF");
                    fputcsv($h, ['(Tabel kosong / Tidak ada data)']);
                    fclose($h);
                }
            }
        } finally {
            fclose($handle);
            foreach ($csvHandles as $h) {
                if (is_resource($h)) fclose($h);
            }
        }

        $result = [];
        foreach ($selectedTables as $tbl) {
            $csvPath = $csvOutputDir . '/' . $tbl . '.csv';
            if (is_file($csvPath)) {
                $result[$tbl] = $csvPath;
            }
        }

        return $result;
    }

    /**
     * Mengonversi berkas CSV tabel menjadi berkas OpenXML .xlsx menggunakan SimpleXlsxExporter.
     */
    protected function convertCsvToXlsx(string $csvPath, string $tableName, string $targetXlsxPath): void
    {
        if (!is_file($csvPath)) {
            return;
        }

        $h = fopen($csvPath, 'r');
        if (!$h) return;

        // Skip UTF-8 BOM jika ada
        $bom = fread($h, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($h);
        }

        $headers = [];
        $rows = [];
        $rowCount = 0;

        $firstRow = fgetcsv($h);
        if ($firstRow && !empty($firstRow)) {
            $headerMap = [];
            foreach ($firstRow as $idx => $colName) {
                $headerMap[$colName ?: ('Kolom_' . ($idx + 1))] = 18;
            }
            $headers = $headerMap;

            // Baca baris data (maksimal 50.000 baris untuk kenyamanan memori sheet tunggal)
            while (($row = fgetcsv($h)) !== false && $rowCount < 50000) {
                $rowCount++;
                $rows[] = $row;
            }
        }
        fclose($h);

        if (empty($headers)) {
            $headers = ['Kolom' => 20];
            $rows = [['(Tabel kosong)']];
        }

        $title = 'DATA ARSIP TABEL: ' . strtoupper($tableName);
        $metaLines = [
            'Total Data: ' . count($rows) . ' Baris Data',
            'Diekstrak dari Arsip .kmh pada: ' . now()->translatedFormat('d F Y, H:i') . ' WIB',
        ];

        $xlsxBinary = SimpleXlsxExporter::create($title, $metaLines, $headers, $rows, [], $tableName);
        file_put_contents($targetXlsxPath, $xlsxBinary);
    }

    /**
     * Ekstrak potongan tuple tuple '(val1, val2, ...)' dari satu baris SQL.
     *
     * @return array<string>
     */
    protected function extractTuplesFromSqlLine(string $content): array
    {
        $tuples = [];
        $inQuotes = false;
        $quoteChar = "'";
        $inTuple = false;
        $tupleBuffer = '';
        $len = strlen($content);

        for ($i = 0; $i < $len; $i++) {
            $c = $content[$i];

            if ($inQuotes) {
                $tupleBuffer .= $c;
                if ($c === '\\') {
                    $i++;
                    if ($i < $len) {
                        $tupleBuffer .= $content[$i];
                    }
                } elseif ($c === $quoteChar) {
                    if ($i + 1 < $len && $content[$i + 1] === $quoteChar) {
                        $tupleBuffer .= $content[$i + 1];
                        $i++;
                    } else {
                        $inQuotes = false;
                    }
                }
            } else {
                if ($c === "'" || $c === '"') {
                    $inQuotes = true;
                    $quoteChar = $c;
                    if ($inTuple) {
                        $tupleBuffer .= $c;
                    }
                } elseif ($c === '(') {
                    $inTuple = true;
                    $tupleBuffer = '';
                } elseif ($c === ')' && $inTuple) {
                    $inTuple = false;
                    $tuples[] = $tupleBuffer;
                    $tupleBuffer = '';
                } elseif ($inTuple) {
                    $tupleBuffer .= $c;
                }
            }
        }

        return $tuples;
    }

    /**
     * Parsing nilai-nilai di dalam satu tuple SQL menjadi array PHP yang bersih.
     *
     * @return array<int, mixed>
     */
    protected function parseTupleValues(string $tupleStr): array
    {
        $values = [];
        $len = strlen($tupleStr);
        $inQuotes = false;
        $curr = '';
        $quoteChar = "'";

        for ($i = 0; $i < $len; $i++) {
            $char = $tupleStr[$i];

            if ($inQuotes) {
                if ($char === '\\') {
                    $next = $tupleStr[$i + 1] ?? '';
                    $curr .= match($next) {
                        'n' => "\n",
                        'r' => "\r",
                        't' => "\t",
                        "'" => "'",
                        '"' => '"',
                        '\\' => '\\',
                        default => $next
                    };
                    $i++;
                } elseif ($char === $quoteChar) {
                    if (($tupleStr[$i + 1] ?? '') === $quoteChar) {
                        $curr .= $quoteChar;
                        $i++;
                    } else {
                        $inQuotes = false;
                    }
                } else {
                    $curr .= $char;
                }
            } else {
                if ($char === "'" || $char === '"') {
                    $inQuotes = true;
                    $quoteChar = $char;
                } elseif ($char === ',') {
                    $val = trim($curr);
                    $values[] = (strcasecmp($val, 'NULL') === 0) ? null : $val;
                    $curr = '';
                } else {
                    $curr .= $char;
                }
            }
        }

        $val = trim($curr);
        $values[] = (strcasecmp($val, 'NULL') === 0) ? null : $val;

        return $values;
    }

    /**
     * Hapus direktori kerja sementara secara menyeluruh.
     */
    public function cleanDirectory(?string $directory): void
    {
        if ($directory && File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }
    }
}
