<?php

namespace App\Livewire\Pages\Admin\Users;

use App\Enums\StudyProgram;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use SimpleXMLElement;
use ZipArchive;

#[Layout('layouts.app', ['showPageHeading' => false])]
class Import extends Component
{
    use WithFileUploads;

    public $file = null;

    public array $rows = [];

    public array $credentials = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->role === UserRole::SUPER_ADMIN, 403);
        abort_unless(auth()->user()->is_active && session()->get('auth_version') === auth()->user()->auth_version && ! auth()->user()->must_change_password, 401);
        $this->rows = session()->get('user_import.preview', []);
    }

    public function preview(): void
    {
        $legacyHeaders = ['user_code', 'name', 'unit_or_prodi'];
        $expected = [...$legacyHeaders, 'nim', 'angkatan'];
        $this->validate(['file' => ['required', 'file', 'max:2048']]);
        $file = $this->file;
        $extension = strtolower($file->getClientOriginalExtension());
        abort_unless(in_array($extension, ['xlsx', 'csv', 'txt'], true), 422, 'Gunakan berkas XLSX atau CSV sesuai template.');
        if ($extension === 'xlsx') {
            $dataRows = $this->readXlsx($file->getRealPath());
        } else {
            $handle = fopen($file->getRealPath(), 'r');
            abort_if($handle === false, 422, 'Berkas tidak dapat dibaca.');
            $dataRows = [];
            while (($fields = fgetcsv($handle)) !== false) {
                $dataRows[] = $fields;
            }
            fclose($handle);
        }
        $headers = $dataRows[0] ?? null;
        abort_unless(in_array($headers, [$legacyHeaders, $expected], true), 422, 'Header harus mengikuti template akun mahasiswa.');
        $hasStudentFields = $headers === $expected;
        $rows = [];
        $seen = [];
        $seenNim = [];
        foreach (array_slice($dataRows, 1) as $fields) {
            if (count(array_filter($fields, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            $row = array_merge(
                ['nim' => '', 'angkatan' => ''],
                array_combine($headers, array_pad(array_slice($fields, 0, count($headers)), count($headers), '')),
            );
            $row['unit_or_prodi'] = trim($row['unit_or_prodi']);
            $code = trim($row['user_code']);
            $errors = [];
            if ($code === '' || strlen($code) > 50 || ! preg_match('/^[A-Za-z0-9_-]+$/', $code)) {
                $errors[] = 'ID pengguna wajib dan hanya boleh berisi huruf, angka, tanda hubung, atau garis bawah.';
            } elseif (isset($seen[strtolower($code)]) || User::query()->whereRaw('LOWER(user_code) = ?', [strtolower($code)])->exists()) {
                $errors[] = 'ID pengguna sudah ada atau duplikat dalam berkas.';
            }
            if (trim($row['name']) === '' || mb_strlen($row['name']) > 255) {
                $errors[] = 'Nama wajib diisi maksimal 255 karakter.';
            }
            if (mb_strlen($row['unit_or_prodi']) > 255) {
                $errors[] = 'Unit atau program studi maksimal 255 karakter.';
            }
            if ($hasStudentFields) {
                if (! in_array($row['unit_or_prodi'], array_column(StudyProgram::cases(), 'value'), true)) {
                    $errors[] = 'Program studi tidak sesuai dengan pilihan yang tersedia.';
                }
                $nim = trim($row['nim']);
                if ($nim === '' || mb_strlen($nim) > 30 || ! preg_match('/^[A-Za-z0-9_-]+$/', $nim)) {
                    $errors[] = 'NIM wajib dan hanya boleh berisi huruf, angka, tanda hubung, atau garis bawah (maksimal 30 karakter).';
                } elseif (isset($seenNim[strtolower($nim)]) || User::query()->whereRaw('LOWER(nim) = ?', [strtolower($nim)])->exists()) {
                    $errors[] = 'NIM sudah digunakan atau duplikat dalam berkas.';
                }
                $angkatan = trim($row['angkatan']);
                if (! ctype_digit($angkatan) || (int) $angkatan < 2000 || (int) $angkatan > now()->year + 1) {
                    $errors[] = 'Angkatan wajib berupa tahun antara 2000 dan '.(now()->year + 1).'.';
                }
                if ($nim !== '') {
                    $seenNim[strtolower($nim)] = true;
                }
            }
            $seen[strtolower($code)] = true;
            $rows[] = [...$row, 'errors' => $errors];
            if (count($rows) > 1000) {
                abort(422, 'Maksimal 1.000 baris per impor.');
            }
        }
        session()->put('user_import.preview', $rows);
        $this->rows = $rows;
        $this->file = null;
    }

    public function downloadTemplate(): mixed
    {
        abort_unless(auth()->user()?->role === UserRole::SUPER_ADMIN, 403);
        $path = tempnam(sys_get_temp_dir(), 'idn-care-template-');
        abort_unless(is_string($path), 500, 'Template Excel tidak dapat dibuat.');

        try {
            $zip = new ZipArchive;
            abort_unless($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'Template Excel tidak dapat dibuat.');
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
            $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Akun" sheetId="1" r:id="rId1"/></sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
            $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>user_code</t></is></c><c r="B1" t="inlineStr"><is><t>name</t></is></c><c r="C1" t="inlineStr"><is><t>unit_or_prodi</t></is></c><c r="D1" t="inlineStr"><is><t>nim</t></is></c><c r="E1" t="inlineStr"><is><t>angkatan</t></is></c></row></sheetData></worksheet>');
            $zip->close();
            $content = file_get_contents($path);
            abort_unless(is_string($content), 500, 'Template Excel tidak dapat dibaca.');
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }

        return response()->streamDownload(function () use ($content): void {
            echo $content;
        }, 'template-akun-idn-care.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function commit(ActivityLogService $logs): void
    {
        abort_unless(session()->has('user_import.preview'), 422, 'Pratinjau impor sudah tidak tersedia. Unggah ulang berkas.');
        $rows = session()->pull('user_import.preview', []);
        $validRows = collect($rows)->filter(fn (array $row) => $row['errors'] === [])->values();
        $credentials = [];
        DB::transaction(function () use ($validRows, &$credentials, $logs): void {
            foreach ($validRows as $row) {
                // Recheck uniqueness within the transaction; existing accounts are never overwritten.
                if (User::query()->whereRaw('LOWER(user_code) = ?', [strtolower($row['user_code'])])->exists()
                    || ($row['nim'] !== '' && User::query()->whereRaw('LOWER(nim) = ?', [strtolower($row['nim'])])->exists())) {
                    continue;
                }
                $password = Str::password(16);
                User::query()->create([
                    'user_code' => $row['user_code'],
                    'name' => $row['name'],
                    'unit_or_prodi' => $row['unit_or_prodi'] ?: null,
                    'nim' => $row['nim'] ?: null,
                    'angkatan' => $row['angkatan'] !== '' ? (int) $row['angkatan'] : null,
                    'role' => UserRole::MAHASISWA,
                    'password' => $password,
                    'is_active' => true,
                    'must_change_password' => true,
                ]);
                $credentials[] = ['user_code' => $row['user_code'], 'password' => $password];
            }
            $logs->record(auth()->user(), 'users_imported', null, [], ['count' => count($credentials)]);
        });

        $this->rows = [];
        $this->credentials = $credentials;
        $this->dispatch('app-notification', message: count($credentials).' akun ditambahkan. Salin password awal sekarang.', type: 'success');
    }

    /** @return array<int, array<int, string>> */
    private function readXlsx(string $path): array
    {
        $zip = new ZipArchive;
        abort_unless($zip->open($path) === true, 422, 'Berkas Excel tidak valid.');
        $totalSize = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $totalSize += (int) ($stat['size'] ?? 0);
        }
        abort_if($totalSize > 10_000_000, 422, 'Isi berkas Excel melebihi batas aman.');
        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $root = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOBLANKS);
            abort_unless($root instanceof SimpleXMLElement, 422, 'Tabel teks Excel tidak valid.');
            $spreadsheetNamespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            foreach ($root->children($spreadsheetNamespace)->si as $item) {
                $text = '';
                foreach ($item->children($spreadsheetNamespace) as $part) {
                    if ($part->getName() === 't') {
                        $text .= (string) $part;
                    } elseif ($part->getName() === 'r') {
                        $text .= (string) $part->children($spreadsheetNamespace)->t;
                    }
                }
                $shared[] = $text;
            }
        }
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        abort_unless(is_string($sheet), 422, 'Lembar pertama tidak ditemukan dalam berkas Excel.');
        $root = simplexml_load_string($sheet, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOBLANKS);
        abort_unless($root !== false, 422, 'Lembar Excel tidak dapat dibaca.');
        $spreadsheetNamespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $rows = [];
        foreach ($root->children($spreadsheetNamespace)->sheetData->children($spreadsheetNamespace)->row as $xmlRow) {
            $values = array_fill(0, 5, '');
            $maxColumn = 0;
            foreach ($xmlRow->children($spreadsheetNamespace)->c as $cell) {
                preg_match('/^([A-Z]+)/', (string) $cell->attributes()->r, $match);
                $column = 0;
                foreach (str_split($match[1] ?? '') as $letter) {
                    $column = $column * 26 + ord($letter) - 64;
                }
                if ($column < 1 || $column > 5) {
                    continue;
                }
                $cellChildren = $cell->children($spreadsheetNamespace);
                $value = (string) $cellChildren->v;
                $inlineValue = isset($cellChildren->is)
                    ? (string) $cellChildren->is->children($spreadsheetNamespace)->t
                    : '';
                $values[$column - 1] = (string) ((string) $cell->attributes()->t === 's'
                    ? ($shared[(int) $value] ?? '')
                    : ($inlineValue !== '' ? $inlineValue : $value));
                $maxColumn = max($maxColumn, $column);
            }
            $rows[] = array_slice($values, 0, $maxColumn);
        }

        return $rows;
    }

    public function render(): View
    {
        return view('livewire.pages.admin.users.import')->title('Pratinjau impor akun');
    }
}
