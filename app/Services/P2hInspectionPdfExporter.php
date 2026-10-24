<?php

namespace App\Services;

use App\Models\P2hRecord;
use Carbon\Carbon;
use FPDF;

class P2hInspectionPdfExporter extends FPDF
{
    public P2hRecord $record;
    public string $logoPath = '';

    public function Header(): void
    {
        // 1. Logo Perusahaan di Kiri Atas
        if (!empty($this->logoPath) && file_exists($this->logoPath)) {
            $this->Image($this->logoPath, 15, 10, 36);
        } else {
            $this->SetXY(15, 10);
            $this->SetFont('Arial', 'B', 13);
            $this->SetTextColor(27, 94, 32);
            $this->Cell(60, 6, 'KERINCI HYDROPOWER', 0, 0, 'L');
        }

        // 2. Branding Sistem di Kanan Atas
        $this->SetXY(100, 10);
        $this->SetFont('Arial', 'B', 15);
        $this->SetTextColor(27, 94, 32); // #1b5e20 Green
        $this->Cell(95, 5, 'RKM - KMH', 0, 1, 'R');

        $this->SetXY(100, 15.5);
        $this->SetFont('Arial', 'B', 7);
        $this->SetTextColor(71, 85, 105); // #475569 Slate
        $this->Cell(95, 4, 'PT KERINCI MERANGIN HIDRO (350 MW)', 0, 1, 'R');

        // 3. Banner Navy Header Dokumen
        $this->SetY(21);
        $this->SetFillColor(6, 33, 63); // #06213f Dark Navy
        $this->Rect(15, 21, 180, 13, 'F');

        // Teks Judul Banner
        $this->SetXY(18, 22.5);
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(115, 5, 'LEMBAR PEMERIKSAAN HARIAN (P2H) HEAVY EQUIPMENT', 0, 1, 'L');

        $this->SetXY(18, 27.5);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(203, 213, 225); // #cbd5e1 Slate
        $this->Cell(115, 4.5, 'Quality & Maintenance Assets (QMA) - Checklist Kelaikan Alat Berat', 0, 1, 'L');

        // Nomor Transaksi & Waktu di Kanan Banner
        $this->SetXY(125, 22.5);
        $this->SetFont('Arial', 'B', 8.5);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(66, 5, $this->record->transaction_no, 0, 1, 'R');

        $this->SetXY(125, 27.5);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(203, 213, 225);
        $this->Cell(66, 4.5, $this->record->inspected_at ? $this->record->inspected_at->format('d/m/Y H:i') . ' WIB' : '-', 0, 1, 'R');

        $this->SetY(36);
    }

    public function Footer(): void
    {
        $this->SetY(-13);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(148, 163, 184);

        $leftText = 'No: ' . $this->record->transaction_no . ' | Unit: ' . ($this->record->vehicle->code ?? '-') . ' | Dicetak: ' . now()->translatedFormat('d F Y, H:i') . ' WIB';
        $this->Cell(130, 5, $leftText, 0, 0, 'L');
        $this->Cell(50, 5, 'Halaman ' . $this->PageNo() . ' dari {nb}', 0, 0, 'R');
    }

    /**
     * Generate Binary PDF String Lembar Pemeriksaan P2H.
     */
    public static function generate(P2hRecord $record): string
    {
        $pdf = new self('P', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->record = $record;
        $pdf->logoPath = public_path('images/logo-kerinci.png');

        $pdf->SetMargins(15, 10, 15);
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 15);

        // -------------------------------------------------------------
        // RINGKASAN METADATA UNIT & OPERATOR (Halaman 1)
        // -------------------------------------------------------------
        $pdf->SetY(37);

        // Box 1: Identitas Heavy Equipment
        $pdf->SetFillColor(248, 250, 252); // #f8fafc
        $pdf->SetDrawColor(226, 232, 240); // #e2e8f0
        $pdf->Rect(15, 37, 58, 30, 'DF');

        $pdf->SetXY(17, 38.5);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->SetTextColor(180, 83, 9); // Amber
        $pdf->Cell(54, 4, 'HEAVY EQUIPMENT', 0, 1, 'L');

        $pdf->SetX(17);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(54, 5, $record->vehicle->code ?? '-', 0, 1, 'L');

        $pdf->SetX(17);
        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell(54, 4, mb_strimwidth($record->vehicle->brand_type ?? '-', 0, 32, '...'), 0, 1, 'L');

        $pdf->SetX(17);
        $pdf->SetFont('Arial', '', 7);
        $catText = 'Kat: ' . ($record->vehicle->unit_category ?? ($record->template->name ?? '-'));
        $pdf->Cell(54, 4, mb_strimwidth($catText, 0, 34, '...'), 0, 1, 'L');

        $pdf->SetX(17);
        $locText = 'Lokasi: ' . ($record->vehicle->location ?? '-');
        $pdf->Cell(54, 4, mb_strimwidth($locText, 0, 34, '...'), 0, 1, 'L');

        // Box 2: Identitas Operator
        $pdf->Rect(76, 37, 58, 30, 'DF');

        $pdf->SetXY(78, 38.5);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->SetTextColor(4, 120, 87); // Emerald
        $pdf->Cell(54, 4, 'OPERATOR (HSE)', 0, 1, 'L');

        $pdf->SetX(78);
        $pdf->SetFont('Arial', 'B', 9.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(54, 5, mb_strimwidth($record->employee->name ?? '-', 0, 26, '...'), 0, 1, 'L');

        $pdf->SetX(78);
        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell(54, 4, 'NIK: ' . ($record->employee->nik ?? '-'), 0, 1, 'L');

        $pdf->SetX(78);
        $pdf->SetFont('Arial', '', 7);
        $divText = 'Divisi: ' . ($record->employee->division ?? '-');
        $pdf->Cell(54, 4, mb_strimwidth($divText, 0, 34, '...'), 0, 1, 'L');

        $pdf->SetX(78);
        $simperText = 'SIMPER: ' . ($record->employee?->simper_expired_at ? $record->employee->simper_expired_at->format('d/m/Y') : 'Non-SIMPER');
        $pdf->Cell(54, 4, mb_strimwidth($simperText, 0, 34, '...'), 0, 1, 'L');

        // Box 3: Data Operasional & Validasi QMA
        $pdf->Rect(137, 37, 58, 30, 'DF');

        $pdf->SetXY(139, 38.5);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->SetTextColor(2, 132, 199); // Sky blue
        $pdf->Cell(54, 4, 'OPERASIONAL & KELAIKAN', 0, 1, 'L');

        $pdf->SetX(139);
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(54, 4.5, $record->shift . ' | ' . number_format($record->start_hm, 1, ',', '.') . ' HM', 0, 1, 'L');

        $pdf->SetX(139);
        $pdf->SetFont('Arial', 'B', 7.5);
        if ($record->hasDefects()) {
            $pdf->SetTextColor(190, 18, 60); // Red
            $pdf->Cell(54, 4, 'Kondisi: ' . $record->defect_count . ' Item Rusak', 0, 1, 'L');
        } else {
            $pdf->SetTextColor(4, 120, 87); // Green
            $pdf->Cell(54, 4, 'Kondisi: LAIK OPERASI (Baik)', 0, 1, 'L');
        }

        $pdf->SetX(139);
        $pdf->SetFont('Arial', 'B', 7.5);
        if ($record->isApproved()) {
            $pdf->SetTextColor(4, 120, 87);
            $pdf->Cell(54, 4, 'Status: Disetujui (Approved)', 0, 1, 'L');
        } elseif ($record->isRejected()) {
            $pdf->SetTextColor(190, 18, 60);
            $pdf->Cell(54, 4, 'Status: Ditolak (Rejected)', 0, 1, 'L');
        } else {
            $pdf->SetTextColor(180, 83, 9);
            $pdf->Cell(54, 4, 'Status: Menunggu Review QMA', 0, 1, 'L');
        }

        $pdf->SetX(139);
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(100, 116, 139);
        $valName = $record->validator?->name ? 'Oleh: ' . mb_strimwidth($record->validator->name, 0, 22, '..') : 'Verifikator: -';
        $pdf->Cell(54, 4, $valName, 0, 1, 'L');

        // Catatan Verifikator / Catatan Umum (Jika ada)
        $nextY = 70;
        if ($record->validation_notes || $record->general_notes) {
            $notesArr = [];
            if ($record->validation_notes) {
                $notesArr[] = 'Catatan QMA: ' . $record->validation_notes;
            }
            if ($record->general_notes) {
                $notesArr[] = 'Catatan Operator: ' . $record->general_notes;
            }
            $notesText = implode(' | ', $notesArr);

            $pdf->SetXY(15, $nextY);
            $pdf->SetFillColor(254, 243, 199); // Amber 100
            $pdf->SetDrawColor(251, 191, 36);  // Amber 400
            $pdf->SetFont('Arial', 'I', 7.5);
            $pdf->SetTextColor(146, 64, 14);   // Amber 800
            $pdf->Cell(180, 6, ' ' . mb_strimwidth($notesText, 0, 115, '...'), 1, 1, 'L', true);
            $nextY += 8;
        } else {
            $nextY += 2;
        }

        // -------------------------------------------------------------
        // TABEL BUTIR PEMERIKSAAN PRA-HARIAN (P2H)
        // -------------------------------------------------------------
        $pdf->SetY($nextY);
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(180, 5, 'Rincian Lembar Checklist Pemeriksaan Komponen:', 0, 1, 'L');

        // Header Kolom Tabel
        $pdf->SetFillColor(6, 33, 63); // Dark navy
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 7.5);

        $pdf->Cell(8, 6.5, 'NO', 0, 0, 'C', true);
        $pdf->Cell(38, 6.5, 'SEKSI / KOMPONEN', 0, 0, 'L', true);
        $pdf->Cell(68, 6.5, 'BUTIR PEMERIKSAAN', 0, 0, 'L', true);
        $pdf->Cell(24, 6.5, 'KONDISI', 0, 0, 'C', true);
        $pdf->Cell(42, 6.5, 'CATATAN TEMUAN', 0, 1, 'L', true);

        // Items Loop
        $groupedItems = [];
        foreach ($record->items as $item) {
            $groupedItems[$item->section][] = $item;
        }

        $pdf->SetDrawColor(226, 232, 240);
        $globalNo = 1;

        foreach ($groupedItems as $sectionTitle => $items) {
            // Cek apakah halaman cukup untuk seksi baru
            if ($pdf->GetY() > 265) {
                $pdf->AddPage();
            }

            // Sub-header Seksi
            $pdf->SetFillColor(241, 245, 249); // Slate 100
            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFont('Arial', 'B', 7.5);
            $pdf->Cell(180, 5.5, ' [ ' . strtoupper($sectionTitle) . ' ] - ' . count($items) . ' Butir Pemeriksaan', 'TB', 1, 'L', true);

            $fill = false;
            foreach ($items as $item) {
                if ($pdf->GetY() > 270) {
                    $pdf->AddPage();
                    // Ulangi Header Kolom di halaman baru
                    $pdf->SetFillColor(6, 33, 63);
                    $pdf->SetTextColor(255, 255, 255);
                    $pdf->SetFont('Arial', 'B', 7.5);
                    $pdf->Cell(8, 6.5, 'NO', 0, 0, 'C', true);
                    $pdf->Cell(38, 6.5, 'SEKSI / KOMPONEN', 0, 0, 'L', true);
                    $pdf->Cell(68, 6.5, 'BUTIR PEMERIKSAAN', 0, 0, 'L', true);
                    $pdf->Cell(24, 6.5, 'KONDISI', 0, 0, 'C', true);
                    $pdf->Cell(42, 6.5, 'CATATAN TEMUAN', 0, 1, 'L', true);
                }

                $pdf->SetFillColor($fill ? 250 : 255, $fill ? 250 : 255, $fill ? 250 : 255);
                $pdf->SetTextColor(15, 23, 42);
                $pdf->SetFont('Arial', '', 7.5);

                $pdf->Cell(8, 6, $globalNo++, 'B', 0, 'C', $fill);
                $pdf->Cell(38, 6, mb_strimwidth($sectionTitle, 0, 24, '...'), 'B', 0, 'L', $fill);
                $pdf->Cell(68, 6, mb_strimwidth($item->title, 0, 48, '...'), 'B', 0, 'L', $fill);

                // Kolom Kondisi
                $cond = strtolower((string)$item->condition_status);
                if ($cond === 'rusak') {
                    $pdf->SetTextColor(190, 18, 60); // Red
                    $pdf->SetFont('Arial', 'B', 7.5);
                    $pdf->Cell(24, 6, 'RUSAK', 'B', 0, 'C', $fill);
                } elseif ($cond === 'baik') {
                    $pdf->SetTextColor(4, 120, 87); // Green
                    $pdf->SetFont('Arial', 'B', 7.5);
                    $pdf->Cell(24, 6, 'BAIK', 'B', 0, 'C', $fill);
                } else {
                    $valStr = !empty($item->answer_value) ? $item->answer_value : '-';
                    $pdf->SetTextColor(71, 85, 105);
                    $pdf->SetFont('Arial', '', 7);
                    $pdf->Cell(24, 6, mb_strimwidth($valStr, 0, 14, '..'), 'B', 0, 'C', $fill);
                }

                // Kolom Catatan Temuan
                $pdf->SetTextColor(71, 85, 105);
                $pdf->SetFont('Arial', $cond === 'rusak' ? 'B' : '', 7);
                $noteVal = !empty($item->notes) ? $item->notes : '-';
                $pdf->Cell(42, 6, mb_strimwidth($noteVal, 0, 28, '...'), 'B', 1, 'L', $fill);

                $fill = !$fill;
            }
        }

        // -------------------------------------------------------------
        // BLOK PENGESAHAN & TANDA TANGAN (3 Kolom)
        // -------------------------------------------------------------
        if ($pdf->GetY() > 240) {
            $pdf->AddPage();
        }

        $pdf->Ln(6);
        $sigY = $pdf->GetY();
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(15, 23, 42);

        // Kolom 1: Operator Lapangan
        $pdf->SetXY(15, $sigY);
        $pdf->Cell(56, 4, 'Diperiksa Oleh (Operator):', 0, 1, 'C');
        $pdf->SetXY(15, $sigY + 16);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(56, 4, '( ' . ($record->employee->name ?? '..................................') . ' )', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(56, 3.5, 'NIK: ' . ($record->employee->nik ?? '-'), 0, 1, 'C');

        // Kolom 2: Verifikator QMA
        $pdf->SetXY(77, $sigY);
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(56, 4, 'Diverifikasi Oleh (Admin QMA):', 0, 1, 'C');
        $pdf->SetXY(77, $sigY + 16);
        $pdf->SetFont('Arial', 'B', 8);
        $valName = $record->validator->name ?? 'Admin QMA';
        $pdf->Cell(56, 4, '( ' . $valName . ' )', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(56, 3.5, 'Quality & Maintenance Assets', 0, 1, 'C');

        // Kolom 3: Pengawas K3 / HSE
        $pdf->SetXY(139, $sigY);
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(56, 4, 'Mengetahui (Pengawas K3/HSE):', 0, 1, 'C');
        $pdf->SetXY(139, $sigY + 16);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(56, 4, '( .............................................. )', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(56, 3.5, 'Safety / HSE Officer', 0, 1, 'C');

        return $pdf->Output('S');
    }
}
