<?php

namespace App\Services;

use App\Models\Letter;
use ZipArchive;

class DocumentExportService
{
    /**
     * Generate a valid binary PDF (PDF-1.4) document for an official letter.
     */
    public function generatePdf(Letter $letter, string $htmlContent): string
    {
        $letterNumber = $letter->letter_number ?: ($letter->ticket_number ?: 'SK/RT01/'.date('Ymd'));
        $citizenName = $letter->citizen?->full_name ?? 'Warga RT 01';
        $letterTitle = strtoupper($letter->letterType?->nama_surat ?? ($letter->type ?: 'Surat Pengantar RT'));
        $purpose = $letter->keperluan ?? 'Keperluan administrasi kependudukan';
        $issuedDate = ($letter->approved_at ?? now())->translatedFormat('d F Y');
        $signer = $letter->approvedBy?->name ?? 'Ketua RT 01';
        $token = $letter->verification_token ?? substr(hash('sha256', $letter->id.$letterNumber), 0, 16);
        $verifyUrl = url('/api/v1/public/letter/verify/'.$token);

        // Prepare clean text lines for standard PDF stream
        $lines = [
            ['font' => 'F2', 'size' => 14, 'text' => 'PEMERINTAH PROVINSI DKI JAKARTA'],
            ['font' => 'F2', 'size' => 12, 'text' => 'KECAMATAN KEMAYORAN - KELURAHAN KEMAYORAN'],
            ['font' => 'F2', 'size' => 12, 'text' => 'RUKUN TETANGGA 01 / RUKUN WARGA 05'],
            ['font' => 'F1', 'size' => 9, 'text' => 'Jl. Kemayoran Raya No. 01, Jakarta Pusat 10620'],
            ['separator' => true],
            ['space' => 15],
            ['font' => 'F2', 'size' => 13, 'text' => $letterTitle],
            ['font' => 'F1', 'size' => 10, 'text' => 'Nomor: '.$letterNumber],
            ['space' => 20],
            ['font' => 'F1', 'size' => 10, 'text' => 'Yang bertanda tangan di bawah ini Ketua RT 01 / RW 05 Kemayoran, menerangkan bahwa:'],
            ['space' => 10],
            ['font' => 'F2', 'size' => 10, 'text' => 'Nama Lengkap       : '.$citizenName],
            ['font' => 'F1', 'size' => 10, 'text' => 'Status Warga       : '.ucfirst($letter->citizen?->status_warga ?? 'Tetap')],
            ['font' => 'F1', 'size' => 10, 'text' => 'Keperluan          : '.$purpose],
            ['space' => 15],
            ['font' => 'F1', 'size' => 10, 'text' => 'Demikian surat keterangan ini diberikan kepada yang bersangkutan untuk dapat dipergunakan'],
            ['font' => 'F1', 'size' => 10, 'text' => 'sebagaimana mestinya sesuai ketentuan yang berlaku.'],
            ['space' => 30],
            ['font' => 'F1', 'size' => 10, 'text' => 'Jakarta, '.$issuedDate],
            ['font' => 'F2', 'size' => 10, 'text' => 'Ketua RT 01 / RW 05'],
            ['space' => 35],
            ['font' => 'F2', 'size' => 10, 'text' => '[ TERTANDA DIGITAL RESMI ]'],
            ['font' => 'F2', 'size' => 10, 'text' => $signer],
            ['space' => 25],
            ['font' => 'F1', 'size' => 8, 'text' => 'Token Verifikasi Dokumen: '.$token],
            ['font' => 'F1', 'size' => 8, 'text' => 'Validasi Keaslian: '.$verifyUrl],
        ];

        return $this->buildPdfBinary($lines, $letterTitle.' - '.$letterNumber);
    }

    /**
     * Generate a valid Microsoft Word (.docx) document using OpenXML PKZip package.
     */
    public function generateDocx(Letter $letter, string $htmlContent): string
    {
        $letterNumber = $letter->letter_number ?: ($letter->ticket_number ?: 'SK/RT01/'.date('Ymd'));
        $citizenName = htmlspecialchars($letter->citizen?->full_name ?? 'Warga RT 01', ENT_XML1);
        $letterTitle = htmlspecialchars(strtoupper($letter->letterType?->nama_surat ?? ($letter->type ?: 'Surat Pengantar RT')), ENT_XML1);
        $purpose = htmlspecialchars($letter->keperluan ?? 'Keperluan administrasi kependudukan', ENT_XML1);
        $issuedDate = htmlspecialchars(($letter->approved_at ?? now())->translatedFormat('d F Y'), ENT_XML1);
        $signer = htmlspecialchars($letter->approvedBy?->name ?? 'Ketua RT 01', ENT_XML1);
        $token = $letter->verification_token ?? substr(hash('sha256', $letter->id.$letterNumber), 0, 16);
        $verifyUrl = htmlspecialchars(url('/api/v1/public/letter/verify/'.$token), ENT_XML1);

        $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rootRels);

        // 3. word/document.xml
        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>
    <w:p>
      <w:pPr><w:jc w:val="center"/></w:pPr>
      <w:r><w:rPr><w:b/><w:sz w:val="28"/></w:rPr><w:t>PEMERINTAH PROVINSI DKI JAKARTA</w:t></w:r>
    </w:p>
    <w:p>
      <w:pPr><w:jc w:val="center"/></w:pPr>
      <w:r><w:rPr><w:b/><w:sz w:val="24"/></w:rPr><w:t>KECAMATAN KEMAYORAN - KELURAHAN KEMAYORAN</w:t></w:r>
    </w:p>
    <w:p>
      <w:pPr><w:jc w:val="center"/></w:pPr>
      <w:r><w:rPr><w:b/><w:sz w:val="24"/></w:rPr><w:t>RUKUN TETANGGA 01 / RUKUN WARGA 05</w:t></w:r>
    </w:p>
    <w:p>
      <w:pPr><w:jc w:val="center"/></w:pPr>
      <w:r><w:rPr><w:sz w:val="18"/><w:color w:val="666666"/></w:rPr><w:t>Jl. Kemayoran Raya No. 01, Jakarta Pusat 10620</w:t></w:r>
    </w:p>
    <w:p>
      <w:pPr>
        <w:pBdr><w:bottom w:val="single" w:sz="12" w:space="4" w:color="000000"/></w:pBdr>
      </w:pPr>
    </w:p>
    <w:p><w:r><w:t></w:t></w:r></w:p>
    <w:p>
      <w:pPr><w:jc w:val="center"/></w:pPr>
      <w:r><w:rPr><w:b/><w:u w:val="single"/><w:sz w:val="26"/></w:rPr><w:t>'.$letterTitle.'</w:t></w:r>
    </w:p>
    <w:p>
      <w:pPr><w:jc w:val="center"/></w:pPr>
      <w:r><w:rPr><w:sz w:val="20"/></w:rPr><w:t>Nomor: '.htmlspecialchars($letterNumber, ENT_XML1).'</w:t></w:r>
    </w:p>
    <w:p><w:r><w:t></w:t></w:r></w:p>
    <w:p>
      <w:r><w:rPr><w:sz w:val="22"/></w:rPr><w:t>Yang bertanda tangan di bawah ini Ketua RT 01 / RW 05 Kemayoran, menerangkan bahwa:</w:t></w:r>
    </w:p>
    <w:p>
      <w:r><w:rPr><w:b/><w:sz w:val="22"/></w:rPr><w:t>Nama Lengkap       : '.$citizenName.'</w:t></w:r>
    </w:p>
    <w:p>
      <w:r><w:rPr><w:sz w:val="22"/></w:rPr><w:t>Keperluan          : '.$purpose.'</w:t></w:r>
    </w:p>
    <w:p><w:r><w:t></w:t></w:r></w:p>
    <w:p>
      <w:r><w:rPr><w:sz w:val="22"/></w:rPr><w:t>Demikian surat keterangan ini diberikan untuk dapat dipergunakan sebagaimana mestinya.</w:t></w:r>
    </w:p>
    <w:p><w:r><w:t></w:t></w:r></w:p>
    <w:p>
      <w:pPr><w:ind w:left="5000"/></w:pPr>
      <w:r><w:rPr><w:sz w:val="22"/></w:rPr><w:t>Jakarta, '.$issuedDate.'</w:t></w:r>
    </w:p>
    <w:p>
      <w:pPr><w:ind w:left="5000"/></w:pPr>
      <w:r><w:rPr><w:b/><w:sz w:val="22"/></w:rPr><w:t>Ketua RT 01 / RW 05</w:t></w:r>
    </w:p>
    <w:p><w:pPr><w:ind w:left="5000"/></w:pPr><w:r><w:t></w:t></w:r></w:p>
    <w:p><w:pPr><w:ind w:left="5000"/></w:pPr><w:r><w:t></w:t></w:r></w:p>
    <w:p>
      <w:pPr><w:ind w:left="5000"/></w:pPr>
      <w:r><w:rPr><w:b/><w:u w:val="single"/><w:sz w:val="22"/></w:rPr><w:t>'.$signer.'</w:t></w:r>
    </w:p>
    <w:p><w:r><w:t></w:t></w:r></w:p>
    <w:p>
      <w:pPr>
        <w:pBdr><w:top w:val="single" w:sz="4" w:space="4" w:color="CCCCCC"/></w:pBdr>
      </w:pPr>
      <w:r><w:rPr><w:sz w:val="16"/><w:color w:val="666666"/></w:rPr><w:t>Token Verifikasi: '.$token.' | Validasi Online: '.$verifyUrl.'</w:t></w:r>
    </w:p>
    <w:sectPr>
      <w:pgSz w:w="11906" w:h="16838"/>
      <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440"/>
    </w:sectPr>
  </w:body>
</w:document>';
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();

        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return $content ?: '';
    }

    /**
     * Builds a standard PDF 1.4 binary file with native vector drawing and text streams.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function buildPdfBinary(array $lines, string $title): string
    {
        $stream = "BT\n";
        $y = 780; // Top margin in points (A4 height is ~841.89)
        $leftMargin = 55;

        foreach ($lines as $item) {
            if (isset($item['separator'])) {
                // End text block, draw line, begin new text block
                $stream .= "ET\n";
                $stream .= "0.5 w\n0 0 0 RG\n";
                $stream .= "{$leftMargin} {$y} m 540 {$y} l S\n";
                $stream .= "BT\n";
                $y -= 8;

                continue;
            }

            if (isset($item['space'])) {
                $y -= (int) $item['space'];

                continue;
            }

            $font = $item['font'] ?? 'F1';
            $size = $item['size'] ?? 10;
            $text = $this->sanitizePdfString((string) ($item['text'] ?? ''));

            $stream .= "/{$font} {$size} Tf\n";
            $stream .= "1 0 0 1 {$leftMargin} {$y} Tm\n";
            $stream .= "({$text}) Tj\n";

            $y -= ($size + 5);
        }

        $stream .= "ET\n";
        $streamLen = strlen($stream);

        // Assemble PDF-1.4 objects
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[3] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[5] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $objects[6] = "<< /Length {$streamLen} >>\nstream\n{$stream}endstream";

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 7\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= 6; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size 7 /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF\n";

        return $pdf;
    }

    protected function sanitizePdfString(string $text): string
    {
        // Escape parentheses and backslashes for PDF string literal
        $escaped = str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\(', '\)'],
            $text
        );

        // Convert non-ASCII characters to standard WinAnsi equivalents
        return iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $escaped) ?: $escaped;
    }
}
