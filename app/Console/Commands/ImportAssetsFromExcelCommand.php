<?php

namespace App\Console\Commands;

use App\Models\P2hTemplate;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class ImportAssetsFromExcelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'units:import-assets
                            {--excel= : Path ke file Excel ASEET KMH.xlsx}
                            {--photos= : Path ke folder FOTO ALAT}
                            {--dry-run : Jalankan simulasi tanpa menulis ke database atau disk}
                            {--force : Timpa foto kendaraan jika sudah ada di storage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import aset Light Vehicle (LV) dan Heavy Equipment (HE) beserta foto dari Berkas Excel ke database';

    /**
     * Pemetaan tipe unit alat berat ke template P2H resmi (28 Kategori).
     */
    protected array $templateAliases = [
        'BOOMLIFT' => 'Boomlift',
        'BORE PILE' => 'Bore Pile Rig',
        'CARRYDECK CRANE' => 'Carry Deck Crane',
        'COMPRESSOR' => 'Engine Compressor',
        'CONCRETE PUMP' => 'Trailer Concrete Pump Electric',
        'CONCRETE PUMP TRUCK' => 'Concrete Pump Truck',
        'CRANE TRUCK' => 'Crane Truck',
        'CRAWLER CRANE' => 'Crawler Crane',
        'CRAWLER ROCK DRILL' => 'Crawler Rig Drill',
        'BULLDOZER' => 'Dozer',
        'DUMP TRUCK' => 'Dump Truck',
        'DUMP TRUCK FFT' => 'Dump Truck',
        'EXCAVATOR' => 'Excavator',
        'FORKLIFT' => 'Forklift',
        'FUEL TRUCK' => 'Fuel Truck',
        'JUMBO DRILL' => 'Jumbo Drill',
        'MOTOR GRADER' => 'Motor Grader',
        'MUCKING LOADER' => 'Mucking Loader',
        'MIXER TRUCK' => 'Truk Mixer',
        'ROUGHTER CRANE' => 'Rough Terrain Crane',
        'SCISSOR LIFT' => 'Scissorlift',
        'SHOTCRETE' => 'Shotcrete',
        'VIBRO' => 'Vibro',
        'WATER TRUCK' => 'Water Truck',
        'WHEEL LOADER' => 'Wheel Loader',
        'WET SHOTCRETE TRUCK' => 'Shotcrete',
    ];

    /**
     * Pemetaan kode singkat kategori untuk master Unit Support.
     */
    protected array $categoryCodePrefixes = [
        'ASPHALT PATCH MIXER' => 'CAT-APM',
        'BOOMLIFT' => 'CAT-BL',
        'CAPSULE CEMENT' => 'CAT-CCM',
        'CARRYDECK CRANE' => 'CAT-CDC',
        'COMPRESSOR' => 'CAT-CMP',
        'CONCRETE PUMP' => 'CAT-CP',
        'CONCRETE PUMP TRUCK' => 'CAT-CPT',
        'CRAWLER CRANE' => 'CAT-CC',
        'CRAWLER ROCK DRILL' => 'CAT-CRD',
        'CRANE TRUCK' => 'CAT-CT',
        'BORE PILE' => 'CAT-BP',
        'DUMP TRUCK' => 'CAT-DT',
        'DUMP TRUCK FFT' => 'CAT-DT-FFT',
        'BULLDOZER' => 'CAT-DZ',
        'ENGINE GENSET' => 'CAT-EG',
        'EXCAVATOR' => 'CAT-EX',
        'FLAT BED' => 'CAT-FB',
        'WATER TRUCK' => 'CAT-WT',
        'FORKLIFT' => 'CAT-FL',
        'FUEL TRUCK' => 'CAT-FT',
        'SILENT SHEET PILE' => 'CAT-SSP',
        'JUMBO DRILL' => 'CAT-JD',
        'LOW BED' => 'CAT-LB',
        'LIGHT TRUCK' => 'CAT-LT',
        'MOBILE CRANE' => 'CAT-MC',
        'MOTOR GRADER' => 'CAT-MG',
        'MIXER TRUCK' => 'CAT-TM',
        'MUCKING LOADER' => 'CAT-ML',
        'ROUGHTER CRANE' => 'CAT-RC',
        'SHOTCRETE' => 'CAT-SC',
        'SCISSOR LIFT' => 'CAT-SL',
        'SKYLIFT TRUCK' => 'CAT-SKT',
        'TOWER CRANE' => 'CAT-TC',
        'TRAILER' => 'CAT-TR',
        'VIBRO' => 'CAT-VB',
        'WHEEL LOADER' => 'CAT-WL',
        'WHEEL RIG DRILL' => 'CAT-WRD',
        'WET SHOTCRETE TRUCK' => 'CAT-WST',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("=== IMPORT ASET KENDARAAN (LV & HEAVY EQUIPMENT) DARI BERKAS EXCEL ===");

        $isDryRun = (bool)$this->option('dry-run');
        $force = (bool)$this->option('force');

        if ($isDryRun) {
            $this->warn("[DRY RUN MODE AKTIF] Perubahan tidak akan disimpan ke database atau filesystem.");
        }

        // 1. Tentukan path file Excel
        $excelPath = $this->resolveExcelPath($this->option('excel'));
        if (!$excelPath || !file_exists($excelPath)) {
            $this->error("File Excel tidak ditemukan di path: " . ($excelPath ?: '(kosong)'));
            $this->line("Harap sediakan opsi --excel=\"C:/path/ke/ASEET KMH.xlsx\"");
            return Command::FAILURE;
        }
        $this->line("File Excel: <info>{$excelPath}</info>");

        // 2. Tentukan path folder foto
        $photosDir = $this->resolvePhotosDir($this->option('photos'));
        if (!$photosDir || !is_dir($photosDir)) {
            $this->error("Direktori foto tidak ditemukan di path: " . ($photosDir ?: '(kosong)'));
            $this->line("Harap sediakan opsi --photos=\"C:/path/ke/FOTO ALAT\"");
            return Command::FAILURE;
        }
        $this->line("Folder Foto: <info>{$photosDir}</info>");

        // 3. Pindai dan indeks seluruh foto
        $this->line("\nMemindai dan mengindeks seluruh file foto...");
        $photoIndex = $this->buildPhotoIndex($photosDir);
        $this->info("Ditemukan " . count($photoIndex['exact']) . " file foto terindeks.");

        // 4. Buka file Excel dengan ZipArchive
        $zip = new ZipArchive();
        if ($zip->open($excelPath) !== true) {
            $this->error("Gagal membuka file Excel sebagai arsip zip (file rusak atau tidak valid).");
            return Command::FAILURE;
        }

        $sharedStrings = $this->extractSharedStrings($zip);
        $sheetFiles = $this->resolveSheetFiles($zip);

        if (!isset($sheetFiles['lv']) || !isset($sheetFiles['he'])) {
            $this->error("Sheet 'LIGTH VEHICLE' atau 'HEAVY EQUIPMENT' tidak ditemukan dalam workbook.");
            $zip->close();
            return Command::FAILURE;
        }

        // Pastikan storage destination directory siap
        $storageVehiclesDir = storage_path('app/public/vehicles');
        if (!$isDryRun && !is_dir($storageVehiclesDir)) {
            File::makeDirectory($storageVehiclesDir, 0755, true, true);
        }

        // Muat seluruh Master Template P2H ke memori
        $templates = P2hTemplate::where('is_active', true)->get();

        // 5. Proses Sheet Light Vehicle
        $this->line("\n--- Memproses Data Light Vehicle (LV) ---");
        $lvStats = $this->importLightVehicles(
            $zip,
            $sheetFiles['lv'],
            $sharedStrings,
            $photoIndex,
            $storageVehiclesDir,
            $isDryRun,
            $force
        );

        // 6. Proses Sheet Heavy Equipment
        $this->line("\n--- Memproses Data Heavy Equipment / Unit Support ---");
        $heStats = $this->importHeavyEquipment(
            $zip,
            $sheetFiles['he'],
            $sharedStrings,
            $photoIndex,
            $templates,
            $storageVehiclesDir,
            $isDryRun,
            $force
        );

        // 6.1 Bersihkan kategori dummy lama tanpa unit & tanpa riwayat transaksi
        if (!$isDryRun) {
            $dummyIds = Vehicle::unitSupportCategories()
                ->whereDoesntHave('units')
                ->whereDoesntHave('activities')
                ->whereDoesntHave('p2hRecords')
                ->whereDoesntHave('supportTimesheets')
                ->whereDoesntHave('transfers')
                ->whereDoesntHave('locationHistories')
                ->whereNotIn('code', array_values($this->categoryCodePrefixes))
                ->pluck('id')
                ->all();

            $cleanedDummies = 0;
            if (!empty($dummyIds)) {
                $cleanedDummies = Vehicle::whereIn('id', $dummyIds)->delete();
            }

            if ($cleanedDummies > 0) {
                $this->line("Membersihkan {$cleanedDummies} kategori dummy lama yang tidak memiliki unit fisik.");
            }
        }

        $zip->close();

        // 7. Cetak Ringkasan Hasil Import
        $this->line("\n================ RINGKASAN HASIL IMPORT ================");
        $this->table(
            ['Kategori Armada', 'Total Data Excel', 'Tersimpan / Terupdate', 'Dengan Foto', 'Persentase Foto'],
            [
                [
                    'Light Vehicle (LV)',
                    $lvStats['total'],
                    $lvStats['saved'],
                    $lvStats['with_photo'],
                    $lvStats['total'] > 0 ? round(($lvStats['with_photo'] / $lvStats['total']) * 100, 1) . '%' : '0%',
                ],
                [
                    'Heavy Equipment (Support)',
                    $heStats['total'],
                    $heStats['saved'],
                    $heStats['with_photo'],
                    $heStats['total'] > 0 ? round(($heStats['with_photo'] / $heStats['total']) * 100, 1) . '%' : '0%',
                ],
                [
                    'Kategori Master Support',
                    $heStats['categories_total'],
                    $heStats['categories_saved'],
                    '-',
                    '-',
                ],
            ]
        );

        $totalVehicles = $lvStats['saved'] + $heStats['saved'];
        $this->info("Import selesai! Total {$totalVehicles} unit operasional berhasil diproses.");

        return Command::SUCCESS;
    }

    /**
     * Resolusi path file Excel dengan fallback otomatis.
     */
    protected function resolveExcelPath(?string $optionPath): ?string
    {
        if (!empty($optionPath)) {
            return $optionPath;
        }

        $candidates = [
            'E:/rkm-kmh/Berkas/ASEET KMH.xlsx',
            base_path('../Berkas/ASEET KMH.xlsx'),
            base_path('Berkas/ASEET KMH.xlsx'),
            'C:/laragon/www/htdocs/Berkas/ASEET KMH.xlsx',
        ];

        foreach ($candidates as $cand) {
            if (file_exists($cand)) {
                return $cand;
            }
        }

        return null;
    }

    /**
     * Resolusi path direktori foto dengan fallback otomatis.
     */
    protected function resolvePhotosDir(?string $optionPath): ?string
    {
        if (!empty($optionPath)) {
            return $optionPath;
        }

        $candidates = [
            'E:/rkm-kmh/Berkas/FOTO ALAT',
            base_path('../Berkas/FOTO ALAT'),
            base_path('Berkas/FOTO ALAT'),
            'C:/laragon/www/htdocs/Berkas/FOTO ALAT',
        ];

        foreach ($candidates as $cand) {
            if (is_dir($cand)) {
                return $cand;
            }
        }

        return null;
    }

    /**
     * Bangun indeks foto dari folder secara rekursif berdasarkan kode unit yang dinormalisasi.
     */
    protected function buildPhotoIndex(string $photosDir): array
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($photosDir));
        $exactIndex = [];
        $allFiles = [];

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                continue;
            }

            $fullPath = str_replace('\\', '/', $file->getPathname());
            $nameWithoutExt = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $normKey = $this->normalizeCode($nameWithoutExt);

            if ($normKey !== '') {
                $exactIndex[$normKey][] = $fullPath;
            }
            $allFiles[$normKey] = $fullPath;
        }

        return [
            'exact' => $exactIndex,
            'all' => $allFiles,
        ];
    }

    /**
     * Normalisasi string kode unit untuk perbandingan tanpa spasi dan tanda baca.
     */
    protected function normalizeCode(string $code): string
    {
        return strtoupper((string)preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    /**
     * Cari foto yang cocok untuk kode unit tertentu.
     */
    protected function findMatchingPhoto(string $code, array $photoIndex): ?string
    {
        $norm = $this->normalizeCode($code);
        if ($norm === '') {
            return null;
        }

        // 1. Cocok persis pada nama file yang dinormalisasi
        if (!empty($photoIndex['exact'][$norm])) {
            return $photoIndex['exact'][$norm][0];
        }

        // 2. Cocok prefix (misal: "LV01_A.jpg" cocok dengan "LV 01")
        foreach ($photoIndex['exact'] as $key => $paths) {
            if (str_starts_with($key, $norm) && !empty($paths)) {
                return $paths[0];
            }
        }

        // 3. Cocok substring di mana kode menjadi token awal
        foreach ($photoIndex['all'] as $key => $path) {
            if (str_contains($key, $norm)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Salin file foto ke folder public storage vehicles.
     */
    protected function copyPhotoToStorage(string $sourcePath, string $cleanCode, string $destDir, bool $force): ?string
    {
        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) ?: 'jpg';
        $fileName = $cleanCode . '.' . $ext;
        $destPath = $destDir . DIRECTORY_SEPARATOR . $fileName;

        if (!file_exists($destPath) || $force) {
            if (!@copy($sourcePath, $destPath)) {
                return null;
            }
        }

        return 'vehicles/' . $fileName;
    }

    /**
     * Ekstrak XML Shared Strings dari file Excel.
     */
    protected function extractSharedStrings(ZipArchive $zip): array
    {
        $sharedStrings = [];
        $xmlContent = $zip->getFromName('xl/sharedStrings.xml');

        if ($xmlContent) {
            $xml = simplexml_load_string($xmlContent);
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string)$r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        return $sharedStrings;
    }

    /**
     * Resolusi nama sheet XML untuk LV dan HE dari workbook.xml.
     */
    protected function resolveSheetFiles(ZipArchive $zip): array
    {
        $wbXml = $zip->getFromName('xl/workbook.xml');
        $sheetFiles = [
            'lv' => 'xl/worksheets/sheet3.xml',
            'he' => 'xl/worksheets/sheet4.xml',
        ];

        if ($wbXml) {
            $xml = simplexml_load_string($wbXml);
            if ($xml && isset($xml->sheets->sheet)) {
                $idx = 1;
                foreach ($xml->sheets->sheet as $s) {
                    $name = strtoupper(trim((string)$s['name']));
                    if (str_contains($name, 'LIGTH') || str_contains($name, 'LIGHT') || str_contains($name, 'LV')) {
                        $sheetFiles['lv'] = "xl/worksheets/sheet{$idx}.xml";
                    } elseif (str_contains($name, 'HEAVY') || str_contains($name, 'EQUIPMENT')) {
                        $sheetFiles['he'] = "xl/worksheets/sheet{$idx}.xml";
                    }
                    $idx++;
                }
            }
        }

        return $sheetFiles;
    }

    /**
     * Parse seluruh baris data worksheet XML menjadi array key-value kolom.
     */
    protected function parseWorksheet(ZipArchive $zip, string $sheetPath, array $sharedStrings): array
    {
        $xmlContent = $zip->getFromName($sheetPath);
        if (!$xmlContent) {
            return [];
        }

        $xml = simplexml_load_string($xmlContent);
        if (!$xml || !isset($xml->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $r) {
            $rNum = (int)$r['r'];
            $cells = [];

            foreach ($r->c as $c) {
                $val = (string)$c->v;
                $type = (string)$c['t'];

                if ($type === 's' && isset($sharedStrings[(int)$val])) {
                    $val = $sharedStrings[(int)$val];
                }

                $col = (string)preg_replace('/[0-9]/', '', (string)$c['r']);
                $cells[$col] = trim($val);
            }

            $rows[$rNum] = $cells;
        }

        return $rows;
    }

    /**
     * Import seluruh data Light Vehicle (LV) dari Sheet 3.
     */
    protected function importLightVehicles(
        ZipArchive $zip,
        string $sheetPath,
        array $sharedStrings,
        array $photoIndex,
        string $destDir,
        bool $isDryRun,
        bool $force
    ): array {
        $rows = $this->parseWorksheet($zip, $sheetPath, $sharedStrings);
        $total = 0;
        $saved = 0;
        $withPhoto = 0;

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach ($rows as $rNum => $r) {
            $bar->advance();

            // Header ada di baris 4, data mulai baris 5
            if ($rNum <= 4) {
                continue;
            }

            $code = trim($r['C'] ?? '');
            if ($code === '') {
                continue;
            }

            $total++;

            $brandType = trim($r['D'] ?? '') ?: 'Light Vehicle';
            $licensePlate = trim($r['F'] ?? '') ?: null;
            $ownership = trim($r['G'] ?? '') ?: 'PT KERINCI MERANGIN HIDRO';
            $rawStatus = strtoupper(trim($r['I'] ?? ''));
            $serialNumber = trim($r['K'] ?? '') ?: null;
            $division = trim($r['M'] ?? '') ?: null;

            // Standarisasi Status
            $status = match ($rawStatus) {
                'OPERATION', 'OPERASIONAL' => 'Operation',
                'STANDBY', 'HO', 'TERJUAL' => 'Standby',
                'BREAKDOWN', 'BRAKEDOWN', 'RUSAK' => 'Breakdown',
                default => 'Operation',
            };

            // Cari foto yang cocok
            $matchedPhoto = $this->findMatchingPhoto($code, $photoIndex);
            $photoPath = null;

            if ($matchedPhoto) {
                $withPhoto++;
                if (!$isDryRun) {
                    $cleanCode = preg_replace('/[^A-Za-z0-9_-]/', '_', strtoupper($code));
                    $photoPath = $this->copyPhotoToStorage($matchedPhoto, $cleanCode, $destDir, $force);
                } else {
                    $photoPath = 'vehicles/' . preg_replace('/[^A-Za-z0-9_-]/', '_', strtoupper($code)) . '.jpg';
                }
            }

            if (!$isDryRun) {
                Vehicle::updateOrCreate(
                    ['code' => $code],
                    [
                        'category' => 'light_vehicle',
                        'parent_id' => null,
                        'p2h_template_id' => null,
                        'brand_type' => $brandType,
                        'license_plate' => $licensePlate,
                        'serial_number' => $serialNumber,
                        'ownership' => $ownership,
                        'division' => $division,
                        'status' => $status,
                        'current_km' => 0,
                        'photo_path' => $photoPath,
                    ]
                );
            }

            $saved++;
        }

        $bar->finish();
        $this->newLine();

        return [
            'total' => $total,
            'saved' => $saved,
            'with_photo' => $withPhoto,
        ];
    }

    /**
     * Import seluruh data Heavy Equipment (Unit Support) dari Sheet 4.
     */
    protected function importHeavyEquipment(
        ZipArchive $zip,
        string $sheetPath,
        array $sharedStrings,
        array $photoIndex,
        $templates,
        string $destDir,
        bool $isDryRun,
        bool $force
    ): array {
        $rows = $this->parseWorksheet($zip, $sheetPath, $sharedStrings);
        $total = 0;
        $saved = 0;
        $withPhoto = 0;

        // 1. Ekstrak seluruh baris data HE valid
        $validRows = [];
        $distinctTypes = [];

        foreach ($rows as $rNum => $r) {
            if ($rNum <= 5) {
                continue;
            }

            $code = trim($r['E'] ?? '');
            if ($code === '') {
                continue;
            }

            $type = strtoupper(trim($r['D'] ?? 'HEAVY EQUIPMENT')) ?: 'HEAVY EQUIPMENT';
            $validRows[] = [
                'row' => $r,
                'code' => $code,
                'type' => $type,
            ];

            $distinctTypes[$type] = true;
        }

        $this->line("Memproses " . count($distinctTypes) . " kategori master alat berat...");

        // 2. Siapkan atau update Master Kategori Unit Support
        $categoryMap = [];
        foreach (array_keys($distinctTypes) as $type) {
            $tmpl = $this->resolveMatchingTemplate($type, $templates);
            $catCode = $this->categoryCodePrefixes[$type] ?? ('CAT-' . strtoupper(Str::slug($type, '-')));
            $catName = $tmpl ? $tmpl->name : ucwords(strtolower($type));

            if (!$isDryRun) {
                $category = Vehicle::updateOrCreate(
                    ['code' => $catCode],
                    [
                        'category' => 'unit_support',
                        'parent_id' => null,
                        'p2h_template_id' => $tmpl?->id,
                        'brand_type' => "Kategori {$catName}",
                        'unit_category' => $catName,
                        'unit_type' => $type,
                        'ownership' => 'PT Kerinci Merangin Hidro',
                        'division' => 'Operasional',
                        'status' => 'Operation',
                        'current_km' => 0,
                    ]
                );
                $categoryMap[$type] = $category;
            } else {
                $categoryMap[$type] = (object)['id' => 1, 'code' => $catCode, 'p2h_template_id' => $tmpl?->id, 'unit_category' => $catName];
            }
        }

        // 3. Proses seluruh unit fisik alat berat
        $bar = $this->output->createProgressBar(count($validRows));
        $bar->start();

        foreach ($validRows as $item) {
            $bar->advance();
            $total++;

            $r = $item['row'];
            $code = $item['code'];
            $type = $item['type'];

            $parentCategory = $categoryMap[$type] ?? null;
            $tmpl = $this->resolveMatchingTemplate($type, $templates);

            $brand = trim($r['I'] ?? '') ?: null;
            $modelNumber = trim($r['J'] ?? '') ?: null;
            $brandType = trim(($brand ?: '') . ' ' . ($modelNumber ?: '')) ?: $type;
            $serialNumber = trim($r['K'] ?? '') ?: null;
            $licensePlate = trim($r['G'] ?? '') ?: null;

            // Tahun produksi
            $rawYear = trim($r['N'] ?? '');
            $prodYear = is_numeric($rawYear) && (int)$rawYear >= 1970 && (int)$rawYear <= (date('Y') + 1)
                ? (int)$rawYear
                : null;

            $location = trim($r['P'] ?? '') ?: 'SITE';
            $siteStatus = $this->determineSiteStatus($location);
            $ownership = trim($r['R'] ?? '') ?: 'PT. BUKAKA';

            // Status operasional
            $status = (str_contains(strtoupper($location), 'STANDBY')) ? 'Standby' : 'Operation';

            // Cari foto yang cocok
            $matchedPhoto = $this->findMatchingPhoto($code, $photoIndex);
            $photoPath = null;

            if ($matchedPhoto) {
                $withPhoto++;
                if (!$isDryRun) {
                    $cleanCode = preg_replace('/[^A-Za-z0-9_-]/', '_', strtoupper($code));
                    $photoPath = $this->copyPhotoToStorage($matchedPhoto, $cleanCode, $destDir, $force);
                } else {
                    $photoPath = 'vehicles/' . preg_replace('/[^A-Za-z0-9_-]/', '_', strtoupper($code)) . '.jpg';
                }
            }

            if (!$isDryRun && $parentCategory) {
                Vehicle::updateOrCreate(
                    ['code' => $code],
                    [
                        'category' => 'unit_support',
                        'parent_id' => $parentCategory->id,
                        'p2h_template_id' => $tmpl?->id ?? $parentCategory->p2h_template_id,
                        'brand_type' => $brandType,
                        'unit_category' => $parentCategory->unit_category ?? ucwords(strtolower($type)),
                        'unit_type' => $type,
                        'brand' => $brand,
                        'model_number' => $modelNumber,
                        'serial_number' => $serialNumber,
                        'license_plate' => $licensePlate,
                        'production_year' => $prodYear,
                        'location' => $location,
                        'site_status' => $siteStatus,
                        'ownership' => $ownership,
                        'status' => $status,
                        'current_km' => 0,
                        'photo_path' => $photoPath,
                    ]
                );
            }

            $saved++;
        }

        $bar->finish();
        $this->newLine();

        return [
            'total' => $total,
            'saved' => $saved,
            'with_photo' => $withPhoto,
            'categories_total' => count($distinctTypes),
            'categories_saved' => count($categoryMap),
        ];
    }

    /**
     * Tentukan site_status ('inside_site' atau 'outside_site') berdasarkan string lokasi.
     */
    protected function determineSiteStatus(?string $location): string
    {
        $locUpper = strtoupper(trim((string)$location));
        if ($locUpper === '') {
            return 'inside_site';
        }

        if (
            str_contains($locUpper, 'JAKARTA') ||
            str_contains($locUpper, 'HO') ||
            str_contains($locUpper, 'LAMPUNG') ||
            str_contains($locUpper, 'ACEH') ||
            str_contains($locUpper, 'CIDAS') ||
            str_contains($locUpper, 'POSO') ||
            str_contains($locUpper, 'MEDCO') ||
            str_contains($locUpper, 'SULINDO')
        ) {
            return 'outside_site';
        }

        return 'inside_site';
    }

    /**
     * Cari template P2H yang cocok untuk tipe unit alat berat.
     */
    protected function resolveMatchingTemplate(string $type, $templates): ?P2hTemplate
    {
        $typeUpper = strtoupper(trim($type));
        $targetName = $this->templateAliases[$typeUpper] ?? null;

        if ($targetName) {
            foreach ($templates as $t) {
                if (strcasecmp($t->name, $targetName) === 0) {
                    return $t;
                }
            }
        }

        // Coba perbandingan langsung jika nama tipe sama dengan nama template
        foreach ($templates as $t) {
            if (strcasecmp($t->name, $type) === 0) {
                return $t;
            }
        }

        return null;
    }
}
