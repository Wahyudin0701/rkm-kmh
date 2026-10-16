<?php

namespace App\Services;

use FPDF;

class VehiclePdfExporter extends FPDF
{
    public string $reportTitle = 'Laporan Data Kendaraan';
    public string $subtitle = '';
    public array $metaLeft = [];
    public array $metaRight = [];
    public array $columns = []; // [['title' => 'NO', 'width' => 10, 'align' => 'C'], ...]
    public int $totalRows = 0;
    public string $logoPath = '';

    public function Header(): void
    {
        // 1. Logo Perusahaan di Kiri Atas
        if (!empty($this->logoPath) && file_exists($this->logoPath)) {
            $this->Image($this->logoPath, 15, 10, 44);
        } else {
            $this->SetXY(15, 10);
            $this->SetFont('Arial', 'B', 14);
            $this->SetTextColor(27, 94, 32);
            $this->Cell(60, 8, 'KERINCI HYDROPOWER', 0, 0, 'L');
        }

        // 2. Branding Sistem di Kanan Atas
        $this->SetXY(170, 10);
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(27, 94, 32); // #1b5e20 Green
        $this->Cell(112, 6, 'RKM-KMH', 0, 1, 'R');

        $this->SetXY(170, 16);
        $this->SetFont('Arial', 'B', 7.5);
        $this->SetTextColor(71, 85, 105); // #475569 Slate
        $this->Cell(112, 4, 'KERINCI HYDROPOWER 350 MW', 0, 1, 'R');

        // 3. Banner Biru Gelap Navy (Sesuai Format it.allrole.web.id)
        $this->SetY(24);
        $this->SetFillColor(6, 33, 63); // #06213f Dark Navy
        $this->Rect(15, 24, 267, 16, 'F');

        // Teks Judul di Kiri Banner
        $this->SetXY(18, 26);
        $this->SetFont('Arial', 'B', 13);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(140, 6, $this->reportTitle, 0, 1, 'L');

        $this->SetXY(18, 32);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(203, 213, 225); // #cbd5e1 Slate
        $this->Cell(140, 5, $this->subtitle, 0, 1, 'L');

        // Teks Sub-sistem di Kanan Banner
        $this->SetXY(160, 26);
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(119, 6, 'SISTEM KENDARAAN RKM-KMH', 0, 1, 'R');

        $this->SetXY(160, 32);
        $this->SetFont('Arial', 'I', 7.5);
        $this->SetTextColor(203, 213, 225);
        $this->Cell(119, 5, 'Dicetak otomatis dari sistem', 0, 1, 'R');

        // 4. Bagian Ringkasan Metadata (2 Kolom)
        $metaY = 44;
        $this->SetTextColor(51, 65, 85);

        // Kolom Kiri
        foreach ($this->metaLeft as $k => $v) {
            $this->SetXY(15, $metaY);
            $this->SetFont('Arial', '', 8);
            $this->Cell(28, 4.5, $k, 0, 0);
            $this->Cell(5, 4.5, ':', 0, 0);
            $this->SetFont('Arial', 'B', 8);
            $this->Cell(85, 4.5, (string) $v, 0, 1);
            $metaY += 5;
        }

        // Kolom Kanan
        $metaY2 = 44;
        foreach ($this->metaRight as $k => $v) {
            $this->SetXY(175, $metaY2);
            $this->SetFont('Arial', '', 8);
            $this->Cell(28, 4.5, $k, 0, 0);
            $this->Cell(5, 4.5, ':', 0, 0);
            $this->SetFont('Arial', 'B', 8);
            $this->Cell(74, 4.5, (string) $v, 0, 1);
            $metaY2 += 5;
        }

        // Garis Pembatas Tipis
        $divY = max($metaY, $metaY2) + 2;
        $this->SetDrawColor(226, 232, 240);
        $this->Line(15, $divY, 282, $divY);

        // 5. Header Tabel Solid Navy
        $tableY = $divY + 3;
        $this->SetXY(15, $tableY);
        $this->SetFillColor(6, 33, 63);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 7.5);

        foreach ($this->columns as $col) {
            $this->Cell($col['width'], 7.5, $col['title'], 0, 0, $col['align'] ?? 'L', true);
        }
        $this->Ln();
    }

    public function Footer(): void
    {
        $this->SetY(-12);
        $this->SetFont('Arial', '', 7.5);
        $this->SetTextColor(148, 163, 184);
        $this->Cell(
            180,
            5,
            'Menampilkan ' . $this->totalRows . ' data - Dokumen ini digenerate secara otomatis oleh Sistem RKM-KMH pada ' . now()->translatedFormat('d F Y') . '.',
            0,
            0,
            'L'
        );
        $this->Cell(87, 5, 'Halaman ' . $this->PageNo() . ' dari {nb}', 0, 0, 'R');
    }

    /**
     * Helper pembuatan file PDF seragam untuk modul armada kendaraan.
     */
    public static function generate(
        string $title,
        string $subtitle,
        array $metaLeft,
        array $metaRight,
        array $columns,
        array $rows
    ): string {
        $pdf = new self('L', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->reportTitle = $title;
        $pdf->subtitle = $subtitle;
        $pdf->metaLeft = $metaLeft;
        $pdf->metaRight = $metaRight;
        $pdf->columns = $columns;
        $pdf->totalRows = count($rows);
        $pdf->logoPath = public_path('images/logo-kerinci.png');

        $pdf->SetMargins(15, 10, 15);
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 15);

        $fill = false;
        foreach ($rows as $r) {
            $pdf->SetFillColor(248, 250, 252);
            $pdf->SetDrawColor(241, 245, 249);
            $pdf->SetTextColor(15, 23, 42);

            foreach ($columns as $idx => $col) {
                $val = (string) ($r[$idx] ?? '-');
                $align = $col['align'] ?? 'L';
                $width = $col['width'];
                $isBold = !empty($col['bold']);
                $isStatus = !empty($col['is_status']);

                if ($isStatus) {
                    if ($val === 'Operation') {
                        $pdf->SetTextColor(4, 120, 87); // Green
                    } elseif ($val === 'Standby') {
                        $pdf->SetTextColor(180, 83, 9); // Amber
                    } elseif ($val === 'Breakdown') {
                        $pdf->SetTextColor(190, 18, 60); // Red
                    } elseif ($val === 'Maintenance') {
                        $pdf->SetTextColor(2, 132, 199); // Sky blue
                    } else {
                        $pdf->SetTextColor(100, 116, 139);
                    }
                    $pdf->SetFont('Arial', 'B', 7.5);
                } else {
                    $pdf->SetTextColor(15, 23, 42);
                    $pdf->SetFont('Arial', $isBold ? 'B' : '', 7.5);
                }

                $maxChar = !empty($col['max_char']) ? $col['max_char'] : 0;
                if ($maxChar > 0 && mb_strlen($val) > $maxChar) {
                    $val = mb_strimwidth($val, 0, $maxChar, '...');
                }

                $displayVal = ($align === 'L' && !empty($val) && $val !== '-') ? ' ' . $val : $val;
                $pdf->Cell($width, 6.5, $displayVal, 'B', 0, $align, $fill);
            }

            $pdf->Ln();
            $fill = !$fill;
        }

        return $pdf->Output('S');
    }
}
