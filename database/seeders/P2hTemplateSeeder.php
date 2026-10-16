<?php

namespace Database\Seeders;

use App\Models\P2hTemplate;
use App\Models\P2hTemplateItem;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class P2hTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dir = base_path('../Data_List_Questions_P2H');
        if (!is_dir($dir)) {
            $dir = 'C:/laragon/www/htdocs/Data_List_Questions_P2H';
        }

        $files = is_dir($dir) ? glob("{$dir}/*.md") : [];

        // 28 Kategori Template Master P2H Unit Support
        $fallbackTemplates = [
            'VGRM7Z' => ['name' => 'Boomlift', 'file_name' => 'P2H_Boomlift_VGRM7Z.md'],
            'VGRM7X' => ['name' => 'Bore Pile Rig', 'file_name' => 'P2H_Bore_Pile_Rig_VGRM7X.md'],
            'CDRM7C' => ['name' => 'Carry Deck Crane', 'file_name' => 'P2H_Carry_Deck_Crane_CDRM7C.md'],
            'YMWL9T' => ['name' => 'Electric Compressor', 'file_name' => 'P2H_Compressor_Electric_YMWL9T.md'],
            'YKWL9C' => ['name' => 'Engine Compressor', 'file_name' => 'P2H_Compressor_Engine_YKWL9C.md'],
            'CPWL9P' => ['name' => 'Trailer Concrete Pump Electric', 'file_name' => 'P2H_Concrete_Pump_Electric_CPWL9P.md'],
            'CPWL9E' => ['name' => 'Portable Concrete Pump Engine', 'file_name' => 'P2H_Concrete_Pump_Engine_CPWL9E.md'],
            '29NVFP' => ['name' => 'Concrete Pump Truck', 'file_name' => 'P2H_Concrete_Pump_Truck_29NVFP.md'],
            'CT723D' => ['name' => 'Crane Truck', 'file_name' => 'P2H_Crane_Truck_CT723D.md'],
            'EH713B' => ['name' => 'Crawler Crane', 'file_name' => 'P2H_Crawler_Crane_EH713B.md'],
            'CR77BD' => ['name' => 'Crawler Rig Drill', 'file_name' => 'P2H_Crawler_Rig_Drill_CR77BD.md'],
            'DZ76BR' => ['name' => 'Dozer', 'file_name' => 'P2H_Dozer_DZ76BR.md'],
            'R8D3SY' => ['name' => 'Dump Truck', 'file_name' => 'P2H_Dump_Truck_R8D3SY.md'],
            'EXD4RP' => ['name' => 'Excavator', 'file_name' => 'P2H_Excavator_EXD4RP.md'],
            'FKD4LY' => ['name' => 'Forklift', 'file_name' => 'P2H_Forklift_FKD4LY.md'],
            'R5D3FT' => ['name' => 'Fuel Truck', 'file_name' => 'P2H_Fuel_Truck_R5D3FT.md'],
            'JD7553D' => ['name' => 'Jumbo Drill', 'file_name' => 'P2H_Jumbo_Drill_JD7553D.md'],
            'RF89BB' => ['name' => 'Manlift', 'file_name' => 'P2H_Manlift_RF89BB.md'],
            'MG79KB' => ['name' => 'Motor Grader', 'file_name' => 'P2H_Motor_Grader_MG79KB.md'],
            'ML71KB' => ['name' => 'Mucking Loader', 'file_name' => 'P2H_Mucking_Loader_ML71KB.md'],
            'EH713C' => ['name' => 'Rough Terrain Crane', 'file_name' => 'P2H_Rough_Terrain_Crane_EH713C.md'],
            'SSLS5W' => ['name' => 'Scissorlift', 'file_name' => 'P2H_Scissorlift_SSLS5W.md'],
            'MS31AC' => ['name' => 'Shotcrete', 'file_name' => 'P2H_Shotcrete_MS31AC.md'],
            'R8D3SW' => ['name' => 'Truk Mixer', 'file_name' => 'P2H_Truk_Mixer_R8D3SW.md'],
            'MV54AH' => ['name' => 'Vibro', 'file_name' => 'P2H_Vibro_MV54AH.md'],
            'CWPK3N' => ['name' => 'Water Pump', 'file_name' => 'P2H_Water_Pump_CWPK3N.md'],
            'WT51DB' => ['name' => 'Water Truck', 'file_name' => 'P2H_Water_Truck_WT51DB.md'],
            'R8D3ST' => ['name' => 'Wheel Loader', 'file_name' => 'P2H_Wheel_Loader_R8D3ST.md'],
        ];

        DB::transaction(function () use ($files, $fallbackTemplates) {
            if (!empty($files)) {
                $this->command->info("Ditemukan " . count($files) . " file template P2H.");
                foreach ($files as $file) {
                    $content = file_get_contents($file);
                    $fileName = basename($file);

                    // 1. Ekstrak Judul Kategori
                    // Pola: # [P2H Excavator](url) atau # P2H Excavator
                    $categoryName = '';
                    if (preg_match('/#\s*\[?P2H\s+([^\]\(\r\n]+)/i', $content, $mTitle)) {
                        $categoryName = trim($mTitle[1]);
                    }

                    // 2. Ekstrak Checksheet Code
                    // Pola: * **Checksheet Code:** `EXD4RP`
                    $checksheetCode = '';
                    if (preg_match('/Checksheet Code:\**\s*`?([A-Za-z0-9]+)`?/i', $content, $mCode)) {
                        $checksheetCode = trim($mCode[1]);
                    }

                    if (!$checksheetCode) {
                        // Fallback kode dari nama file jika tidak ditemukan regex
                        $checksheetCode = strtoupper(Str::slug($categoryName, '_'));
                    }

                    $template = P2hTemplate::updateOrCreate(
                        ['code' => $checksheetCode],
                        [
                            'name' => $categoryName ?: pathinfo($fileName, PATHINFO_FILENAME),
                            'file_name' => $fileName,
                            'description' => "Template form inspeksi P2H resmi untuk kategori {$categoryName} (Kode: {$checksheetCode}).",
                            'is_active' => true,
                        ]
                    );

                    // Bersihkan item lama jika seeder dijalankan ulang
                    $template->items()->delete();

                    // 3. Parse Baris Tabel Markdown
                    $lines = explode("\n", $content);
                    $currentSection = 'General';
                    $sequence = 1;

                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (str_starts_with($line, '|')) {
                            $cols = array_map('trim', explode('|', $line));
                            // cols format: [0]=>'', [1]=>No, [2]=>Section, [3]=>Title, [4]=>Question Type, [5]=>Sequence, [6]=>''
                            if (count($cols) >= 6 && is_numeric($cols[1])) {
                                $rawSection = trim(str_replace('**', '', $cols[2]));
                                $title = trim($cols[3]);
                                $rawType = trim($cols[4]);

                                // Jika baris adalah penanda Header Section
                                if (stripos($title, 'Header Section') !== false || $rawType === '-') {
                                    if (!empty($rawSection)) {
                                        $currentSection = $rawSection;
                                    }
                                    continue;
                                }

                                // Abaikan baris penutup jika berupa baris survey kosong
                                if (stripos($title, 'Penutup') !== false || stripos($rawSection, 'Penutup') !== false) {
                                    continue;
                                }

                                if (!empty($rawSection) && $rawSection !== $currentSection) {
                                    $currentSection = $rawSection;
                                }

                                // Map question type
                                $qType = match(true) {
                                    stripos($rawType, 'Datetime') !== false => 'datetime',
                                    stripos($rawType, 'Numerical') !== false => 'number',
                                    stripos($rawType, 'Multiple Lines') !== false => 'text',
                                    stripos($rawType, 'Single Line') !== false => 'text',
                                    default => 'choice',
                                };

                                P2hTemplateItem::create([
                                    'template_id' => $template->id,
                                    'section' => $currentSection,
                                    'title' => $title,
                                    'question_type' => $qType,
                                    'sequence' => $sequence++,
                                ]);
                            }
                        }
                    }
                }
            } else {
                // Fallback: Gunakan daftar 28 master template bawaan jika folder markdown tidak ada
                $this->command->info("Menggunakan 28 template master P2H bawaan.");
                $defaultQuestions = [
                    'Pemeriksaan Keliling (Walk Around)' => [
                        'Kondisi fisik bodi, struktur, dan kebersihan unit',
                        'Pemeriksaan kebocoran oli, air pendingin radiator, dan bahan bakar',
                        'Kondisi ban / track shoe, baut roda, dan tegangan track',
                    ],
                    'Ruang Mesin & Kompartemen' => [
                        'Level oli mesin, oli hidrolik, dan minyak rem',
                        'Level air radiator dan kondisi v-belt / fan belt',
                        'Kondisi baterai / aki dan kebersihan terminal kabel',
                    ],
                    'Kabin & Fungsi Operasional' => [
                        'Fungsi sistem rem (Service Brake & Parking Brake)',
                        'Fungsi sistem kemudi, tuas hidrolik, dan instrumen indikator',
                        'Fungsi lampu kerja, lampu rotary, klakson, dan alarm mundur',
                        'Ketersediaan APD standar dan APAR yang valid di dalam kabin',
                    ],
                ];

                foreach ($fallbackTemplates as $code => $tmplData) {
                    $template = P2hTemplate::updateOrCreate(
                        ['code' => $code],
                        [
                            'name' => $tmplData['name'],
                            'file_name' => $tmplData['file_name'],
                            'description' => "Template form inspeksi P2H resmi untuk kategori {$tmplData['name']} (Kode: {$code}).",
                            'is_active' => true,
                        ]
                    );

                    $template->items()->delete();
                    $sequence = 1;
                    foreach ($defaultQuestions as $section => $questions) {
                        foreach ($questions as $qTitle) {
                            P2hTemplateItem::create([
                                'template_id' => $template->id,
                                'section' => $section,
                                'title' => $qTitle,
                                'question_type' => 'choice',
                                'sequence' => $sequence++,
                            ]);
                        }
                    }
                }
            }

            // 4. Data Unit Kendaraan Support untuk Seluruh 28 Kategori P2H
            $defaultUnits = [
                'VGRM7Z' => ['code' => 'BL01', 'brand_type' => 'GENIE Z-45/25J DC BOOMLIFT', 'unit_type' => 'Articulating Boomlift', 'brand' => 'Genie', 'km' => 850.0, 'loc' => 'Powerhouse Area'],
                'VGRM7X' => ['code' => 'BP01', 'brand_type' => 'BAUER BG 28 H ROTARY DRILL', 'unit_type' => 'Bore Pile Rig', 'brand' => 'Bauer', 'km' => 1240.0, 'loc' => 'Inlet Dam'],
                'CDRM7C' => ['code' => 'CD01', 'brand_type' => 'BRODERSON IC-200-3J CARRY CRANE', 'unit_type' => 'Carry Deck Crane 15T', 'brand' => 'Broderson', 'km' => 980.0, 'loc' => 'Workshop Utama'],
                'YMWL9T' => ['code' => 'CE01', 'brand_type' => 'ATLAS COPCO GA 75 VSD ELECTRIC', 'unit_type' => 'Electric Compressor', 'brand' => 'Atlas Copco', 'km' => 2100.0, 'loc' => 'Tunnel Adit 1'],
                'YKWL9C' => ['code' => 'CG01', 'brand_type' => 'SULLAIR 375H DIESEL ENGINE', 'unit_type' => 'Engine Compressor', 'brand' => 'Sullair', 'km' => 1650.0, 'loc' => 'Regulating Weir'],
                'CPWL9P' => ['code' => 'CPE01', 'brand_type' => 'PUTZMEISTER BSA 1407 D PORTABLE', 'unit_type' => 'Trailer Concrete Pump Electric', 'brand' => 'Putzmeister', 'km' => 740.0, 'loc' => 'Spillway Area'],
                'CPWL9E' => ['code' => 'CPG01', 'brand_type' => 'SCHWING SP 500 DIESEL PUMP', 'unit_type' => 'Portable Concrete Pump Engine', 'brand' => 'Schwing', 'km' => 890.0, 'loc' => 'Surge Tank'],
                '29NVFP' => ['code' => 'CPT01', 'brand_type' => 'SANY SY36 RZ5-130 PUMP TRUCK', 'unit_type' => 'Concrete Pump Truck 36M', 'brand' => 'Sany', 'km' => 3120.0, 'loc' => 'CCR DAM'],
                'CT723D' => ['code' => 'CT01', 'brand_type' => 'ISUZU GIGA FVR 34P TADANO 5T', 'unit_type' => 'Crane Truck Cargo', 'brand' => 'Isuzu', 'km' => 4500.0, 'loc' => 'Gudang Induk'],
                'EH713B' => ['code' => 'CC01', 'brand_type' => 'KOBELCO 7055-2 55T CRAWLER', 'unit_type' => 'Crawler Crane Lattice', 'brand' => 'Kobelco', 'km' => 2800.0, 'loc' => 'Bendungan Utama'],
                'CR77BD' => ['code' => 'CRD01', 'brand_type' => 'SANDVIK DX700 SURFACE DRILL', 'unit_type' => 'Crawler Rig Drill', 'brand' => 'Sandvik', 'km' => 1950.0, 'loc' => 'Quarry Area'],
                'DZ76BR' => ['code' => 'DZ01', 'brand_type' => 'KOMATSU D85ESS-2 DOZER', 'unit_type' => 'Crawler Bulldozer', 'brand' => 'Komatsu', 'km' => 4200.0, 'loc' => 'Disposal Area'],
                'R8D3SY' => ['code' => 'DT02', 'brand_type' => 'MITSUBISHI FUSO FIGHTER FN62F', 'unit_type' => 'Dump Truck 20 Ton', 'brand' => 'Mitsubishi', 'km' => 14800.0, 'loc' => 'Quarry & Disposal'],
                'EXD4RP' => ['code' => 'EX01', 'brand_type' => 'KOMATSU PC200-8 EXCAVATOR', 'unit_type' => 'Hydraulic Excavator', 'brand' => 'Komatsu', 'km' => 3450.0, 'loc' => 'Beringin Tunggal / Tunnel #2'],
                'FKD4LY' => ['code' => 'FL01', 'brand_type' => 'TOYOTA 8FD30 DIESEL 3.0T', 'unit_type' => 'Industrial Forklift', 'brand' => 'Toyota', 'km' => 1100.0, 'loc' => 'Gudang Logistik'],
                'R5D3FT' => ['code' => 'FT01', 'brand_type' => 'HINO RANGER FM 260 JD FUEL 10KL', 'unit_type' => 'Fuel Dispenser Truck', 'brand' => 'Hino', 'km' => 8200.0, 'loc' => 'Stasiun Pengisian BBM'],
                'JD7553D' => ['code' => 'JD01', 'brand_type' => 'EPIROC BOOMER 282 JUMBO DRILL', 'unit_type' => 'Underground Jumbo Drill', 'brand' => 'Epiroc', 'km' => 1450.0, 'loc' => 'Tunnel Adit 2'],
                'RF89BB' => ['code' => 'MN01', 'brand_type' => 'MANITOU MT-X 1840 TELEHANDLER', 'unit_type' => 'Telescopic Handler', 'brand' => 'Manitou', 'km' => 1350.0, 'loc' => 'Powerhouse Yard'],
                'MG79KB' => ['code' => 'MG01', 'brand_type' => 'CATERPILLAR 140K MOTOR GRADER', 'unit_type' => 'Motor Grader 14ft', 'brand' => 'Caterpillar', 'km' => 2900.0, 'loc' => 'Jalan Akses Utama'],
                'ML71KB' => ['code' => 'ML01', 'brand_type' => 'ITC 312 H6 TUNNEL LOADER', 'unit_type' => 'Tunnel Mucking Loader', 'brand' => 'ITC', 'km' => 920.0, 'loc' => 'Tunnel Adit 3'],
                'EH713C' => ['code' => 'CR01', 'brand_type' => 'KATO SR-250R 25T ROUGH CRANE', 'unit_type' => 'Rough Terrain Crane', 'brand' => 'Kato', 'km' => 1820.0, 'loc' => 'CCR DAM / Spillway'],
                'SSLS5W' => ['code' => 'SL01', 'brand_type' => 'JLG 1930ES ELECTRIC SCISSOR', 'unit_type' => 'Electric Scissorlift', 'brand' => 'JLG', 'km' => 620.0, 'loc' => 'Powerhouse Interior'],
                'MS31AC' => ['code' => 'SC01', 'brand_type' => 'NORMET SPRAYMEC 8100 VC', 'unit_type' => 'Tunnel Shotcrete Sprayer', 'brand' => 'Normet', 'km' => 1180.0, 'loc' => 'Tunnel Main Header'],
                'R8D3SW' => ['code' => 'TM01', 'brand_type' => 'HINO DUTRO 130 HD TRUK MIXER', 'unit_type' => 'Transit Mixer 3m3', 'brand' => 'Hino', 'km' => 5400.0, 'loc' => 'Batching Plant'],
                'MV54AH' => ['code' => 'VB01', 'brand_type' => 'BOMAG BW 211 D-4 VIBRO ROLLER', 'unit_type' => 'Single Drum Roller', 'brand' => 'Bomag', 'km' => 2600.0, 'loc' => 'Dam Embankment'],
                'CWPK3N' => ['code' => 'WP01', 'brand_type' => 'SELWOOD S150 WATER PUMP 6 INCH', 'unit_type' => 'Dewatering Water Pump', 'brand' => 'Selwood', 'km' => 1550.0, 'loc' => 'Cofferdam Sump'],
                'WT51DB' => ['code' => 'WT01', 'brand_type' => 'ISUZU GIGA FVR WATER TRUCK 8KL', 'unit_type' => 'Water Sprayer Truck', 'brand' => 'Isuzu', 'km' => 6700.0, 'loc' => 'Hauling Road'],
                'R8D3ST' => ['code' => 'WL01', 'brand_type' => 'CATERPILLAR 950H WHEEL LOADER', 'unit_type' => 'Wheel Loader 3.5m3', 'brand' => 'Caterpillar', 'km' => 3800.0, 'loc' => 'Crusher Plant'],
            ];

            foreach ($defaultUnits as $tmplCode => $uData) {
                $tmpl = P2hTemplate::where('code', $tmplCode)->first();
                if (!$tmpl) continue;

                $existing = Vehicle::where('code', $uData['code'])->first();
                if ($existing) {
                    $existing->update([
                        'p2h_template_id' => $tmpl->id,
                        'unit_category' => $tmpl->name,
                    ]);
                } else {
                    Vehicle::create([
                        'code' => $uData['code'],
                        'category' => 'unit_support',
                        'p2h_template_id' => $tmpl->id,
                        'brand_type' => $uData['brand_type'],
                        'unit_category' => $tmpl->name,
                        'unit_type' => $uData['unit_type'],
                        'brand' => $uData['brand'],
                        'ownership' => 'PT Kerinci Merangin Hidro',
                        'division' => 'Operasional',
                        'location' => $uData['loc'],
                        'status' => 'Operation',
                        'current_km' => $uData['km'],
                    ]);
                }
            }
        });

        $this->command->info("Seeder P2H berhasil! Seluruh 28 template kategori telah diimpor.");
    }
}
