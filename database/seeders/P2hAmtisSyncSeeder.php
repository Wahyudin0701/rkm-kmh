<?php

namespace Database\Seeders;

use App\Models\P2hTemplate;
use App\Models\P2hTemplateItem;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class P2hAmtisSyncSeeder extends Seeder
{
    /**
     * Pemetaan nama sheet di P2H..xlsx ke nama/kode template resmi di database.
     */
    protected array $sheetMapping = [
        'EX' => [
            'name' => 'Excavator',
            'code' => 'EXD4RP',
            'description' => 'Checksheet resmi P2H unit Excavator (SOP Amtis KMH).',
        ],
        'DT' => [
            'name' => 'Dump Truck',
            'code' => 'R8D3SY',
            'description' => 'Checksheet resmi P2H armada Dump Truck proyek (SOP Amtis KMH).',
        ],
        'CRC' => [
            'name' => 'Crawler Crane',
            'code' => 'EH713B',
            'description' => 'Checksheet resmi P2H unit Crawler Crane (SOP Amtis KMH).',
        ],
        'RC' => [
            'name' => 'Rough Terrain Crane',
            'code' => 'EH713C',
            'description' => 'Checksheet resmi P2H unit Rough Terrain Crane (SOP Amtis KMH).',
        ],
        'WL' => [
            'name' => 'Wheel Loader',
            'code' => 'R8D3ST',
            'description' => 'Checksheet resmi P2H unit Wheel Loader (SOP Amtis KMH).',
        ],
        'DZ' => [
            'name' => 'Dozer',
            'code' => 'DZ76BR',
            'description' => 'Checksheet resmi P2H unit Bulldozer / Dozer (SOP Amtis KMH).',
        ],
        'VB' => [
            'name' => 'Vibro',
            'code' => 'MV54AH',
            'description' => 'Checksheet resmi P2H unit Vibro Compactor (SOP Amtis KMH).',
        ],
        'MIX' => [
            'name' => 'Truk Mixer',
            'code' => 'R8D3SW',
            'description' => 'Checksheet resmi P2H armada Truk Mixer (SOP Amtis KMH).',
        ],
        'FL' => [
            'name' => 'Forklift',
            'code' => 'FKD4LY',
            'description' => 'Checksheet resmi P2H unit Forklift (SOP Amtis KMH).',
        ],
        'FT' => [
            'name' => 'Fuel Truck',
            'code' => 'R5D3FT',
            'description' => 'Checksheet resmi P2H armada Fuel Truck (SOP Amtis KMH).',
        ],
        'BL' => [
            'name' => 'Boomlift',
            'code' => 'BL01',
            'description' => 'Checksheet resmi P2H unit Boomlift (SOP Amtis KMH).',
        ],
        'DR' => [
            'name' => 'Bore Pile Rig',
            'code' => 'VGRM7X',
            'description' => 'Checksheet resmi P2H unit Bore Pile Rig / Drilling (SOP Amtis KMH).',
        ],
        'COM ENGINE' => [
            'name' => 'Engine Compressor',
            'code' => 'YKWL9C',
            'description' => 'Checksheet resmi P2H unit Compressor Engine (SOP Amtis KMH).',
        ],
        'CP ELEKTRIK' => [
            'name' => 'Trailer Concrete Pump Electric',
            'code' => 'CPWL9P',
            'description' => 'Checksheet resmi P2H Trailer Concrete Pump Electric (SOP Amtis KMH).',
        ],
        'CP ENGINE' => [
            'name' => 'Portable Concrete Pump Engine',
            'code' => 'CPWL9E',
            'description' => 'Checksheet resmi P2H Portable Concrete Pump Engine (SOP Amtis KMH).',
        ],
        'CT' => [
            'name' => 'Crane Truck',
            'code' => 'CT723D',
            'description' => 'Checksheet resmi P2H armada Crane Truck (SOP Amtis KMH).',
        ],
        'CRD' => [
            'name' => 'Crawler Rig Drill',
            'code' => 'CR77BD',
            'description' => 'Checksheet resmi P2H unit Crawler Rig Drill (SOP Amtis KMH).',
        ],
        'MANITAU' => [
            'name' => 'Manitou (Telehandler)',
            'code' => 'MNT01',
            'description' => 'Checksheet resmi P2H unit Manitou Telehandler (SOP Amtis KMH).',
        ],
        'MG' => [
            'name' => 'Motor Grader',
            'code' => 'MG79KB',
            'description' => 'Checksheet resmi P2H unit Motor Grader (SOP Amtis KMH).',
        ],
        'WT' => [
            'name' => 'Water Truck',
            'code' => 'WT51DB',
            'description' => 'Checksheet resmi P2H armada Water Truck (SOP Amtis KMH).',
        ],
    ];

    public function run(): void
    {
        $filePath = base_path('Berkas/P2H..xlsx');
        if (!file_exists($filePath)) {
            $filePath = 'E:/rkm-kmh/Berkas/P2H..xlsx';
        }

        if (!file_exists($filePath)) {
            $this->command?->error("File P2H..xlsx tidak ditemukan di {$filePath}");
            return;
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            $this->command?->error("Gagal membuka file zip {$filePath}");
            return;
        }

        // 1. Ekstrak Shared Strings
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml) {
            $xml = simplexml_load_string($ssXml);
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string)$si->t;
                } elseif (isset($si->r)) {
                    $t = '';
                    foreach ($si->r as $r) {
                        $t .= (string)$r->t;
                    }
                    $sharedStrings[] = $t;
                } else {
                    $sharedStrings[] = '';
                }
            }
        }

        // 2. Baca Workbook Sheet Listing
        $wbXml = $zip->getFromName('xl/workbook.xml');
        $xml = simplexml_load_string($wbXml);
        $sheets = [];
        $idx = 1;
        foreach ($xml->sheets->sheet as $sheet) {
            $sheets[(string)$sheet['name']] = $idx++;
        }

        $metaWords = ['start', 'shift', 'hm', 'section', 'result'];

        DB::transaction(function () use ($zip, $sheets, $sharedStrings, $metaWords) {
            $totalSyncedTemplates = 0;
            $totalSyncedItems = 0;

            foreach ($this->sheetMapping as $sheetKey => $meta) {
                if (!isset($sheets[$sheetKey])) {
                    continue;
                }

                $sheetIdx = $sheets[$sheetKey];
                $sheetXml = $zip->getFromName("xl/worksheets/sheet{$sheetIdx}.xml");
                if (!$sheetXml) continue;
                $sXml = simplexml_load_string($sheetXml);

                // Cari atau buat template di DB
                $template = P2hTemplate::where('code', $meta['code'])
                    ->orWhere('name', $meta['name'])
                    ->first();

                if (!$template) {
                    $template = P2hTemplate::create([
                        'code' => $meta['code'],
                        'name' => $meta['name'],
                        'description' => $meta['description'],
                        'is_active' => true,
                    ]);
                } else {
                    $template->update([
                        'name' => $meta['name'],
                        'description' => $meta['description'],
                        'is_active' => true,
                    ]);
                }

                // Parse baris-baris checklist
                $currentSection = 'Sebelum Mesin Hidup';
                $sequence = 1;
                $itemsToCreate = [];

                foreach ($sXml->sheetData->row as $row) {
                    $cols = [];
                    foreach ($row->c as $cell) {
                        $col = preg_replace('/[0-9]/', '', (string)$cell['r']);
                        $val = (string)$cell->v;
                        if ((string)$cell['t'] === 's' && isset($sharedStrings[(int)$val])) {
                            $val = $sharedStrings[(int)$val];
                        }
                        $cols[$col] = trim($val);
                    }

                    $b = $cols['B'] ?? '';
                    $c = $cols['C'] ?? '';
                    $d = $cols['D'] ?? '';

                    // Lewati title / header Section / Result
                    if (stripos($b, 'P2H ') === 0 || stripos($c, 'P2H ') === 0) continue;
                    if (strtolower($b) === 'section' || strtolower($c) === 'section') continue;

                    // Lewati baris metadata (Start datetime, Shift, HM)
                    if (in_array(strtolower($b), ['start', 'shift', 'hm']) && in_array(strtolower($c), ['start', 'shift', 'hm', 'datetime', 'multiple choice: only one answer', 'numerical value'])) continue;
                    if (in_array(strtolower($c), ['start', 'shift', 'hm']) && in_array(strtolower($d), ['datetime', 'multiple choice: only one answer', 'numerical value'])) continue;

                    // Cek apakah baris ini adalah nama section header
                    $isSectionHeader = false;
                    $candidateSec = '';

                    if (!empty($b) && empty($c) && empty($d)) {
                        $isSectionHeader = true;
                        $candidateSec = $b;
                    } elseif (!empty($c) && empty($d) && empty($b)) {
                        $isSectionHeader = true;
                        $candidateSec = $c;
                    }

                    if ($isSectionHeader) {
                        if (strtolower($candidateSec) !== 'start') {
                            $norm = ucwords(strtolower(trim($candidateSec)));
                            if (stripos($norm, 'sebelum') !== false) {
                                $currentSection = 'Sebelum Mesin Hidup';
                            } elseif (stripos($norm, 'persiapan') !== false) {
                                $currentSection = 'Persiapan Menghidupkan Mesin';
                            } elseif (stripos($norm, 'setelah') !== false) {
                                $currentSection = 'Setelah Mesin Menyala';
                            } else {
                                $currentSection = $norm;
                            }
                        }
                        continue;
                    }

                    // Ambil judul pertanyaan & tentukan tipenya
                    $qTitle = '';
                    $qType = 'choice';

                    if (!empty($b) && !empty($c)) {
                        if (stripos($c, 'Multiple choice') !== false || stripos($c, 'Multiple Lines') !== false || stripos($c, 'Numerical') !== false) {
                            $qTitle = $b;
                            $qType = (stripos($c, 'Multiple Lines') !== false) ? 'text' : 'choice';
                        } elseif (in_array(strtolower($b), ['sebelum mesin hidup', 'persiapan menghidupkan mesin', 'setelah mesin menyala'])) {
                            $currentSection = ucwords(strtolower($b));
                            if (stripos($currentSection, 'sebelum') !== false) $currentSection = 'Sebelum Mesin Hidup';
                            elseif (stripos($currentSection, 'persiapan') !== false) $currentSection = 'Persiapan Menghidupkan Mesin';
                            elseif (stripos($currentSection, 'setelah') !== false) $currentSection = 'Setelah Mesin Menyala';
                            $qTitle = $c;
                            if (!empty($d) && stripos($d, 'Multiple Lines') !== false) $qType = 'text';
                        } else {
                            $qTitle = $b;
                        }
                    } elseif (!empty($c) && !empty($d)) {
                        $qTitle = $c;
                        if (stripos($d, 'Multiple Lines') !== false) $qType = 'text';
                    }

                    $qTitleClean = trim(preg_replace('/\s+/', ' ', $qTitle));
                    if (!empty($qTitleClean) && !in_array(strtolower($qTitleClean), $metaWords)) {
                        $itemsToCreate[] = [
                            'template_id' => $template->id,
                            'section' => $currentSection,
                            'title' => $qTitleClean,
                            'question_type' => $qType,
                            'sequence' => $sequence++,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if (!empty($itemsToCreate)) {
                    // Bersihkan butir lama dan masukkan butir resmi Amtis
                    P2hTemplateItem::where('template_id', $template->id)->delete();
                    foreach ($itemsToCreate as $it) {
                        P2hTemplateItem::create($it);
                    }

                    $totalSyncedTemplates++;
                    $totalSyncedItems += count($itemsToCreate);
                }

                // Asosiasikan unit kendaraan berat yang relevan ke template ini
                Vehicle::where('category', 'unit_support')
                    ->where(function ($q) use ($meta) {
                        $q->where('unit_category', 'like', "%{$meta['name']}%")
                          ->orWhere('brand_type', 'like', "%{$meta['name']}%");
                    })
                    ->update(['p2h_template_id' => $template->id]);
            }

            // Tautkan kendaraan spesifik yang memiliki nama mirip
            Vehicle::where('category', 'unit_support')
                ->where('unit_category', 'Truk Mixer')
                ->update(['p2h_template_id' => P2hTemplate::where('code', 'R8D3SW')->value('id')]);

            Vehicle::where('category', 'unit_support')
                ->where('unit_category', 'Dump Truck')
                ->update(['p2h_template_id' => P2hTemplate::where('code', 'R8D3SY')->value('id')]);

            Vehicle::where('category', 'unit_support')
                ->where('unit_category', 'Excavator')
                ->update(['p2h_template_id' => P2hTemplate::where('code', 'EXD4RP')->value('id')]);
        });

        $zip->close();
    }
}
