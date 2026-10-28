<?php

namespace App\Services;

use Carbon\Carbon;
use ZipArchive;

class SimpleXlsxImporter
{
    /**
     * Parse an XLSX or CSV file into an array of rows (each row is an array of cell strings).
     *
     * @param string $filePath
     * @param string|null $originalExtension
     * @return array<int, array<int, string>>
     */
    public static function parse(string $filePath, ?string $originalExtension = null): array
    {
        $ext = strtolower($originalExtension ?: pathinfo($filePath, PATHINFO_EXTENSION));

        // Jika ekstensi jelas CSV atau TXT
        if ($ext === 'csv' || $ext === 'txt') {
            return self::parseCsv($filePath);
        }

        // Periksa magic bytes ZIP ("PK\x03\x04"). XLSX selalu berupa file ZIP
        $handle = @fopen($filePath, 'rb');
        $magic = $handle ? fread($handle, 4) : '';
        if ($handle) {
            fclose($handle);
        }

        if ($magic === "PK\x03\x04") {
            return self::parseXlsx($filePath);
        }

        // Fallback coba baca sebagai CSV
        return self::parseCsv($filePath);
    }

    /**
     * Parse CSV with delimiter detection.
     *
     * @param string $filePath
     * @return array<int, array<int, string>>
     */
    public static function parseCsv(string $filePath): array
    {
        $rows = [];
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return $rows;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return $rows;
        }

        // Deteksi delimiter: cek baris pertama
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = array_map(fn($v) => trim((string)$v), $data);
        }
        fclose($handle);

        return $rows;
    }

    /**
     * Parse OpenXML XLSX via ZipArchive and SimpleXML.
     *
     * @param string $filePath
     * @return array<int, array<int, string>>
     */
    public static function parseXlsx(string $filePath): array
    {
        $rows = [];
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return $rows;
        }

        // 1. Baca shared strings jika tersedia
        $sharedStrings = [];
        $sharedXmlContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXmlContent) {
            $xml = @simplexml_load_string($sharedXmlContent, 'SimpleXMLElement', LIBXML_NONET);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) ($r->t ?? '');
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Baca sheet1 data
        $sheetXmlContent = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetXmlContent) {
            $zip->close();
            return $rows;
        }

        $xml = @simplexml_load_string($sheetXmlContent, 'SimpleXMLElement', LIBXML_NONET);
        $zip->close();

        if (!$xml || !isset($xml->sheetData->row)) {
            return $rows;
        }

        foreach ($xml->sheetData->row as $row) {
            $rowCells = [];
            $maxColIdx = 0;

            foreach ($row->c as $c) {
                $ref = (string) $c['r']; // e.g. "B2"
                $colLetters = preg_replace('/[0-9]/', '', $ref);
                $colIdx = self::colIndex($colLetters);

                $t = (string) ($c['t'] ?? '');
                $val = '';

                if ($t === 's') {
                    $idx = (int) $c->v;
                    $val = $sharedStrings[$idx] ?? '';
                } elseif ($t === 'inlineStr') {
                    $val = (string) ($c->is->t ?? '');
                } elseif ($t === 'b') {
                    $val = ((string) $c->v === '1') ? '1' : '0';
                } else {
                    $val = (string) ($c->v ?? '');
                }

                $rowCells[$colIdx] = trim($val);
                if ($colIdx > $maxColIdx) {
                    $maxColIdx = $colIdx;
                }
            }

            // Normalisasi array kolom dari indeks 1 sampai maxColIdx
            $normalizedRow = [];
            for ($i = 1; $i <= $maxColIdx; $i++) {
                $normalizedRow[] = $rowCells[$i] ?? '';
            }

            // Abaikan jika baris kosong total
            if (array_filter($normalizedRow, fn($v) => $v !== '') !== []) {
                $rows[] = $normalizedRow;
            }
        }

        return $rows;
    }

    /**
     * Konversi kode kolom Excel (A, B, ..., Z, AA, AB) ke indeks integer 1-based.
     */
    private static function colIndex(string $col): int
    {
        $col = strtoupper($col);
        $len = strlen($col);
        $idx = 0;
        for ($i = 0; $i < $len; $i++) {
            $idx = $idx * 26 + (ord($col[$i]) - ord('A') + 1);
        }
        return $idx ?: 1;
    }

    /**
     * Normalisasi nilai tanggal dari Excel (bisa berupa teks yyyy-mm-dd, dd/mm/yyyy, atau serial Excel integer).
     */
    public static function normalizeDate(?string $value): ?string
    {
        if (!$value || trim($value) === '' || trim($value) === '-') {
            return null;
        }

        $value = trim($value);

        // Jika berupa angka serial tanggal Excel (misal: 45657)
        if (is_numeric($value) && (float) $value > 20000 && (float) $value < 80000) {
            try {
                return Carbon::create(1899, 12, 30)->addDays((int) $value)->toDateString();
            } catch (\Throwable) {
                // fallback
            }
        }

        // Coba parsing format umum dd/mm/yyyy atau dd-mm-yyyy
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $value, $matches)) {
            try {
                return sprintf('%04d-%02d-%02d', (int) $matches[3], (int) $matches[2], (int) $matches[1]);
            } catch (\Throwable) {
                // fallback
            }
        }

        // Parsing standar Carbon
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
