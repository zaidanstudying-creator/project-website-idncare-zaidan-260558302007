<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArchivedReportExportService
{
    private const HEADERS = ['Tiket', 'Judul', 'Jenis laporan', 'Tahun ajaran', 'Status', 'Tanggal arsip'];

    public function download(string $format): Response
    {
        $filename = 'arsip-laporan-'.now()->format('Ymd-His');

        return match ($format) {
            'csv' => $this->csv($filename.'.csv'),
            'xlsx' => $this->excel($filename.'.xlsx'),
            'pdf' => response($this->pdf(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.pdf"',
            ]),
        };
    }

    private function csv(string $filename): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, self::HEADERS, ',', '"', '');

            foreach ($this->reports() as $report) {
                $row = array_map(fn (string $value): string => preg_match('/^[\s=+\-@]/u', $value) ? "'".$value : $value, $this->row($report));
                fputcsv($stream, $row, ',', '"', '');
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function excel(string $filename): BinaryFileResponse
    {
        $sheetPath = tempnam(sys_get_temp_dir(), 'idn-care-sheet-');
        $archivePath = tempnam(sys_get_temp_dir(), 'idn-care-xlsx-');
        $stream = fopen($sheetPath, 'w');
        fwrite($stream, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>');
        $this->writeExcelRow($stream, self::HEADERS, 1);
        $rowNumber = 2;
        foreach ($this->reports() as $report) {
            $this->writeExcelRow($stream, $this->row($report), $rowNumber++);
        }
        fwrite($stream, '</sheetData></worksheet>');
        fclose($stream);

        $archive = new \ZipArchive;
        $opened = $archive->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if ($opened !== true) {
            unlink($sheetPath);
            unlink($archivePath);
            abort(500, 'Berkas Excel tidak dapat dibuat.');
        }
        $archive->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>');
        $archive->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>');
        $archive->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Arsip" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $archive->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>');
        $archive->addFile($sheetPath, 'xl/worksheets/sheet1.xml');
        $archive->close();
        unlink($sheetPath);

        return response()->download($archivePath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    private function writeExcelRow($stream, array $values, int $rowNumber): void
    {
        fwrite($stream, '<row r="'.$rowNumber.'">');
        foreach ($values as $index => $value) {
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $value) ?? '';
            $value = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            fwrite($stream, '<c r="'.$this->columnName($index + 1).$rowNumber.'" t="inlineStr"><is><t xml:space="preserve">'.$value.'</t></is></c>');
        }
        fwrite($stream, '</row>');
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    private function pdf(): string
    {
        $total = Report::withoutGlobalScope('active')->whereNotNull('archived_at')->count();
        $pageCount = max(1, (int) ceil($total / 33));
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pageReferences = [];
        $pageRows = [];
        $pageIndex = 0;
        $appendPage = function (array $rows) use (&$objects, &$pageReferences, &$pageIndex, $pageCount, $total): void {
            $pageObject = 4 + ($pageIndex * 2);
            $contentObject = $pageObject + 1;
            $pageReferences[] = $pageObject.' 0 R';
            $content = $this->pdfPageContent($rows, $pageIndex + 1, $pageCount, $total);
            $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] '
                .'/Resources << /Font << /F1 3 0 R >> >> /Contents '.$contentObject.' 0 R >>';
            $objects[$contentObject] = '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream";
            $pageIndex++;
        };

        foreach ($this->reports() as $report) {
            $pageRows[] = array_map(fn (string $value): string => Str::limit($this->ascii($value), 38, '...'), $this->row($report));
            if (count($pageRows) === 33) {
                $appendPage($pageRows);
                $pageRows = [];
            }
        }
        if ($pageRows !== [] || $pageReferences === []) {
            $appendPage($pageRows);
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $pageReferences).'] /Count '.$pageCount.' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number." 0 obj\n".$object."\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $size = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 {$size}\n0000000000 65535 f \n";
        for ($number = 1; $number < $size; $number++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number] ?? 0);
        }
        $pdf .= "trailer\n<< /Size {$size} /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function pdfPageContent(array $rows, int $page, int $pages, int $total): string
    {
        $content = "BT /F1 17 Tf 36 558 Td (Arsip laporan IDN CARE) Tj /F1 8 Tf 0 -18 Td (Total: {$total} laporan | Halaman {$page} dari {$pages}) Tj ET\n";
        $columns = [36, 118, 300, 408, 514, 590];
        $y = 510;
        foreach ([self::HEADERS, ...$rows] as $rowIndex => $row) {
            $fontSize = $rowIndex === 0 ? 8 : 7;
            foreach ($row as $index => $value) {
                $text = $this->pdfString($value);
                $content .= "BT /F1 {$fontSize} Tf 1 0 0 1 {$columns[$index]} {$y} Tm ({$text}) Tj ET\n";
            }
            $content .= '0.86 0.9 0.95 RG 36 '.($y - 5).' m 806 '.($y - 5)." l S\n";
            $y -= 14;
        }

        return $content;
    }

    private function reports(): iterable
    {
        return Report::withoutGlobalScope('active')->whereNotNull('archived_at')
            ->with('reportType:id,code,name')->orderBy('id')->lazyById(500);
    }

    private function row(Report $report): array
    {
        return [
            $report->ticket_no,
            $report->title,
            $report->reportType?->name ?? '—',
            $report->academic_year ?? '—',
            ucfirst($report->status),
            $report->archived_at?->timezone('Asia/Jakarta')->format('d-m-Y H:i') ?? '—',
        ];
    }

    private function ascii(string $value): string
    {
        return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: '';
    }

    private function pdfString(string $value): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $value);
    }
}
