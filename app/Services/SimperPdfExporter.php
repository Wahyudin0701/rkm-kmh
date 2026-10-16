<?php

namespace App\Services;

use FPDF;
use Illuminate\Support\Collection;

class SimperPdfExporter extends FPDF
{
    public string $reportTitle = 'Laporan Data SIMPER';
    public string $subtitle = '';
    public array $metaLeft = [];
    public array $metaRight = [];
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

        // 3. Banner Biru Gelap Navy (Sesuai Referensi it.allrole.web.id)
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
            $this->Cell(85, 4.5, $v, 0, 1);
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
            $this->Cell(74, 4.5, $v, 0, 1);
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

        $cols = [
            ['NO', 10, 'C'],
            ['NIK', 30, 'C'],
            ['NAMA KARYAWAN', 62, 'L'],
            ['JABATAN', 45, 'L'],
            ['UNIT / DIVISI', 40, 'L'],
            ['STATUS DRIVER', 26, 'C'],
            ['MASA BERLAKU', 26, 'C'],
            ['STATUS SIMPER', 28, 'C'],
        ];

        foreach ($cols as $col) {
            $this->Cell($col[1], 7.5, $col[0], 0, 0, $col[2], true);
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
     * Generate real PDF binary string.
     */
    public static function generate(Collection $employees, array $metaLeft, array $metaRight, string $subtitle): string
    {
        $pdf = new self('L', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->reportTitle = 'Laporan Data SIMPER';
        $pdf->subtitle = $subtitle;
        $pdf->metaLeft = $metaLeft;
        $pdf->metaRight = $metaRight;
        $pdf->totalRows = $employees->count();
        $pdf->logoPath = public_path('images/logo-kerinci.png');

        $pdf->SetMargins(15, 10, 15);
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 15);

        $pdf->SetFont('Arial', '', 7.5);
        $fill = false;
        $soon = now()->addDays(30);

        foreach ($employees as $idx => $emp) {
            $isDriver = $emp->is_operator;
            $expDate = $emp->simper_expired_at ? $emp->simper_expired_at->format('d/m/Y') : '-';

            if (! $isDriver) {
                $statusText = 'Bukan Driver';
            } elseif (! $emp->simper_expired_at) {
                $statusText = 'Belum Ada SIMPER';
            } elseif ($emp->simper_expired_at->isPast() && ! $emp->simper_expired_at->isToday()) {
                $statusText = 'Kedaluwarsa';
            } elseif ($emp->simper_expired_at->lte($soon)) {
                $statusText = 'Segera Habis';
            } else {
                $statusText = 'Masih Berlaku';
            }

            $pdf->SetFillColor(248, 250, 252);
            $pdf->SetDrawColor(241, 245, 249);
            $pdf->SetTextColor(15, 23, 42);

            // Kolom 1: No
            $pdf->SetFont('Arial', '', 7.5);
            $pdf->Cell(10, 6.5, (string) ($idx + 1), 'B', 0, 'C', $fill);

            // Kolom 2: NIK (Bold)
            $pdf->SetFont('Arial', 'B', 7.5);
            $pdf->Cell(30, 6.5, $emp->nik, 'B', 0, 'C', $fill);

            // Kolom 3: Nama Lengkap (Bold, Truncate jika > 36 karakter)
            $nama = mb_strimwidth($emp->name, 0, 36, '...');
            $pdf->Cell(62, 6.5, ' ' . $nama, 'B', 0, 'L', $fill);

            // Kolom 4: Jabatan
            $pdf->SetFont('Arial', '', 7.5);
            $jabatan = mb_strimwidth($emp->position ?: '-', 0, 26, '...');
            $pdf->Cell(45, 6.5, ' ' . $jabatan, 'B', 0, 'L', $fill);

            // Kolom 5: Unit / Divisi
            $unit = mb_strimwidth($emp->unit_kerja ?: ($emp->division ?: '-'), 0, 24, '...');
            $pdf->Cell(40, 6.5, ' ' . $unit, 'B', 0, 'L', $fill);

            // Kolom 6: Status Driver
            $pdf->Cell(26, 6.5, $isDriver ? 'Driver' : 'Non-Driver', 'B', 0, 'C', $fill);

            // Kolom 7: Masa Berlaku
            $pdf->Cell(26, 6.5, $expDate, 'B', 0, 'C', $fill);

            // Kolom 8: Status SIMPER (Berwarna)
            if ($statusText === 'Masih Berlaku') {
                $pdf->SetTextColor(4, 120, 87); // #047857 Green
            } elseif ($statusText === 'Segera Habis') {
                $pdf->SetTextColor(180, 83, 9); // #b45309 Amber
            } elseif ($statusText === 'Kedaluwarsa' || $statusText === 'Belum Ada SIMPER') {
                $pdf->SetTextColor(190, 18, 60); // #be123c Rose
            } else {
                $pdf->SetTextColor(100, 116, 139); // #64748b Slate
            }
            $pdf->SetFont('Arial', 'B', 7.5);
            $pdf->Cell(28, 6.5, $statusText, 'B', 0, 'C', $fill);

            $pdf->Ln();
            $fill = ! $fill;
        }

        return $pdf->Output('S');
    }
}
