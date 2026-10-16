<?php

namespace App\Services;

use ZipArchive;

class SimpleXlsxExporter
{
    /**
     * Generate standard OpenXML XLSX binary string.
     *
     * @param string $title
     * @param array $metaLines e.g. ['Filter: Semua', 'Tanggal Cetak: 16-10-2026 09:30']
     * @param array $headers e.g. ['No' => 6, 'NIK' => 16, 'Nama Lengkap' => 28, ...]
     * @param array $rows Array of associative or index arrays
     * @param array $alignments Optional per-column alignment: 'center' or 'left'
     * @return string Raw zip content
     */
    public static function create(string $title, array $metaLines, array $headers, array $rows, array $alignments = []): string
    {
        $numCols = count($headers);
        $lastColLetter = self::colLetter($numCols);

        // Build sheetData XML
        $sheetXml = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $sheetXml .= '<sheetPr><outlinePr summaryBelow="1" summaryRight="1" /><pageSetUpPr /></sheetPr>';
        
        $totalRows = 4 + count($metaLines) + count($rows);
        $sheetXml .= '<dimension ref="A1:' . $lastColLetter . $totalRows . '" />';
        $sheetXml .= '<sheetViews><sheetView workbookViewId="0"><selection activeCell="A1" sqref="A1" /></sheetView></sheetViews>';
        $sheetXml .= '<sheetFormatPr baseColWidth="8" defaultRowHeight="16" />';

        // Column widths
        $sheetXml .= '<cols>';
        $colIdx = 1;
        foreach ($headers as $name => $width) {
            $sheetXml .= '<col min="' . $colIdx . '" max="' . $colIdx . '" width="' . (is_numeric($width) ? $width : 15) . '" customWidth="1" />';
            $colIdx++;
        }
        $sheetXml .= '</cols>';

        $sheetXml .= '<sheetData>';

        // Row 1: Title
        $rowNum = 1;
        $sheetXml .= '<row r="' . $rowNum . '" ht="26" customHeight="1">';
        $sheetXml .= '<c r="A1" s="1" t="inlineStr"><is><t>' . htmlspecialchars($title, ENT_XML1) . '</t></is></c>';
        $sheetXml .= '</row>';

        // Meta lines (Sub-titles)
        foreach ($metaLines as $meta) {
            $rowNum++;
            $sheetXml .= '<row r="' . $rowNum . '" ht="18" customHeight="1">';
            $sheetXml .= '<c r="A' . $rowNum . '" s="2" t="inlineStr"><is><t>' . htmlspecialchars($meta, ENT_XML1) . '</t></is></c>';
            $sheetXml .= '</row>';
        }

        // Blank spacer row
        $rowNum++;
        $sheetXml .= '<row r="' . $rowNum . '" ht="10" customHeight="1"></row>';

        // Table Header Row
        $rowNum++;
        $headerRowNum = $rowNum;
        $sheetXml .= '<row r="' . $headerRowNum . '" ht="24" customHeight="1">';
        $cIdx = 1;
        foreach ($headers as $name => $width) {
            $cellRef = self::colLetter($cIdx) . $headerRowNum;
            $sheetXml .= '<c r="' . $cellRef . '" s="3" t="inlineStr"><is><t>' . htmlspecialchars($name, ENT_XML1) . '</t></is></c>';
            $cIdx++;
        }
        $sheetXml .= '</row>';

        // Table Data Rows
        $alignKeys = array_values($alignments);
        foreach ($rows as $rData) {
            $rowNum++;
            $sheetXml .= '<row r="' . $rowNum . '" ht="20" customHeight="1">';
            $cIdx = 1;
            $vals = is_array($rData) ? array_values($rData) : [];
            for ($i = 0; $i < $numCols; $i++) {
                $val = $vals[$i] ?? '';
                $cellRef = self::colLetter($cIdx) . $rowNum;
                $align = $alignKeys[$i] ?? ($cIdx === 1 ? 'center' : 'left');
                $styleId = ($align === 'center') ? 4 : 5;

                if (is_numeric($val) && strlen($val) > 0 && !str_starts_with((string)$val, '0')) {
                    $sheetXml .= '<c r="' . $cellRef . '" s="' . $styleId . '" t="n"><v>' . $val . '</v></c>';
                } else {
                    $sheetXml .= '<c r="' . $cellRef . '" s="' . $styleId . '" t="inlineStr"><is><t>' . htmlspecialchars((string)$val, ENT_XML1) . '</t></is></c>';
                }
                $cIdx++;
            }
            $sheetXml .= '</row>';
        }

        $sheetXml .= '</sheetData>';

        // Merge cells for title and meta lines
        $sheetXml .= '<mergeCells count="' . (1 + count($metaLines)) . '">';
        $sheetXml .= '<mergeCell ref="A1:' . $lastColLetter . '1" />';
        for ($m = 2; $m <= 1 + count($metaLines); $m++) {
            $sheetXml .= '<mergeCell ref="A' . $m . ':' . $lastColLetter . $m . '" />';
        }
        $sheetXml .= '</mergeCells>';

        $sheetXml .= '<pageMargins left="0.5" right="0.5" top="0.75" bottom="0.75" header="0.3" footer="0.3" />';
        $sheetXml .= '</worksheet>';

        // Stylesheet with KMH Brand Colors (Navy / Forest Green)
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="4">
        <font><name val="Arial" /><sz val="10" /></font>
        <font><name val="Arial" /><sz val="14" /><b val="1" /><color rgb="FF0F172A" /></font>
        <font><name val="Arial" /><sz val="9" /><i val="1" /><color rgb="FF64748B" /></font>
        <font><name val="Arial" /><sz val="10" /><b val="1" /><color rgb="FFFFFFFF" /></font>
    </fonts>
    <fills count="3">
        <fill><patternFill patternType="none" /></fill>
        <fill><patternFill patternType="gray125" /></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF06213F" /><bgColor rgb="FF06213F" /></patternFill></fill>
    </fills>
    <borders count="2">
        <border><left /><right /><top /><bottom /></border>
        <border>
            <left style="thin"><color rgb="FFE2E8F0" /></left>
            <right style="thin"><color rgb="FFE2E8F0" /></right>
            <top style="thin"><color rgb="FFE2E8F0" /></top>
            <bottom style="thin"><color rgb="FFE2E8F0" /></bottom>
        </border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" />
    </cellStyleXfs>
    <cellXfs count="6">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" />
        <xf numFmtId="0" fontId="1" fillId="0" borderId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" /></xf>
        <xf numFmtId="0" fontId="2" fillId="0" borderId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" /></xf>
        <xf numFmtId="0" fontId="3" fillId="2" borderId="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1" /></xf>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyAlignment="1"><alignment horizontal="center" vertical="center" /></xf>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyAlignment="1"><alignment horizontal="left" vertical="center" /></xf>
    </cellXfs>
</styleSheet>';

        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheets>
        <sheet name="Data SIMPER" sheetId="1" r:id="rId1" />
    </sheets>
</workbook>';

        $workbookRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml" />
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml" />
</Relationships>';

        $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml" />
</Relationships>';

        $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml" />
    <Default Extension="xml" ContentType="application/xml" />
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml" />
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml" />
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml" />
</Types>';

        // Build ZIP
        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $contentTypesXml);
        $zip->addFromString('_rels/.rels', $relsXml);
        $zip->addFromString('xl/workbook.xml', $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRelsXml);
        $zip->addFromString('xl/styles.xml', $stylesXml);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        $zip->close();

        $content = file_get_contents($tmpFile);
        unlink($tmpFile);

        return $content;
    }

    private static function colLetter(int $colIndex): string
    {
        $letter = '';
        while ($colIndex > 0) {
            $p = ($colIndex - 1) % 26;
            $letter = chr(65 + $p) . $letter;
            $colIndex = intval(($colIndex - $p) / 26);
        }
        return $letter ?: 'A';
    }
}
