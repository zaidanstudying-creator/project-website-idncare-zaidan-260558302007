<?php

namespace App\Livewire\Pages\Reports;

use App\Enums\UserRole;
use App\Models\Location;
use App\Models\ReportType;
use App\Services\ReportSubmissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app', ['showPageHeading' => false])]
class CreateForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $typeCode;

    public string $title = '';

    public string $description = '';

    public string $categoryId = '';

    public ?string $locationId = null;

    public string $requestedVisibility = 'umum';

    public bool $isAnonymous = false;

    public ?string $incidentDate = null;

    public string $relatedParty = '';

    /** @var array<int, mixed> */
    public array $photos = [];

    public function mount(string $type): void
    {
        abort_unless(auth()->user()?->role === UserRole::MAHASISWA, 403);
        ReportType::query()->where('code', $type)->firstOrFail();
        $this->typeCode = $type;
    }

    public function submit(ReportSubmissionService $submission): void
    {
        $reportType = ReportType::query()->where('code', $this->typeCode)->firstOrFail();
        $isFacility = $reportType->code === ReportType::FACILITAS;
        $isViolation = $reportType->code === ReportType::PELANGGARAN;
        $requiresLocation = $isFacility || $isViolation;
        $key = config('app.report_key');

        $data = $this->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:10000'],
            'categoryId' => [
                'required', 'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where('report_type_id', $reportType->id)->where('is_active', true)),
            ],
            'locationId' => [
                ...($requiresLocation ? ['required'] : ['nullable']),
                'integer', Rule::exists('locations', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'photos' => $isFacility ? ['required', 'array', 'min:1', 'max:5'] : ['nullable', 'array', 'max:5'],
            'photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:4096'],
            'requestedVisibility' => $isViolation ? ['prohibited'] : ['required', Rule::in(['umum', 'rahasia'])],
            'isAnonymous' => ['boolean'],
            'incidentDate' => $isViolation ? ['required', 'date', 'before_or_equal:today'] : ['prohibited'],
            'relatedParty' => $isViolation ? ['nullable', 'string', 'max:255'] : ['prohibited'],
        ], [
            'categoryId.exists' => 'Pilih kategori aktif untuk jenis laporan ini.',
            'locationId.exists' => 'Pilih lokasi yang masih aktif.',
            'photos.required' => 'Unggah minimal satu foto kerusakan.',
            'photos.min' => 'Unggah minimal satu foto kerusakan.',
            'photos.max' => 'Maksimal lima foto dapat diunggah.',
            'photos.*.image' => 'Setiap lampiran harus berupa gambar.',
            'photos.*.mimes' => 'Format foto yang didukung adalah JPG, JPEG, dan PNG.',
            'photos.*.extensions' => 'Ekstensi setiap foto harus sesuai dengan format JPG, JPEG, atau PNG.',
            'photos.*.max' => 'Ukuran setiap foto maksimal 4 MB.',
            'requestedVisibility.prohibited' => 'Laporan dugaan pelanggaran selalu bersifat rahasia.',
            'incidentDate.before_or_equal' => 'Tanggal kejadian tidak boleh di masa depan.',
        ]);

        if ($isViolation && $data['isAnonymous'] && (! is_string($key) || strlen($key) < 32)) {
            throw ValidationException::withMessages([
                'isAnonymous' => 'Pelaporan anonim sementara belum tersedia karena konfigurasi perlindungan belum siap. Hubungi administrator; laporan belum terkirim.',
            ]);
        }

        $submission->submit(auth()->user(), $this->typeCode, [
            'title' => $data['title'],
            'description' => $data['description'],
            'category_id' => $data['categoryId'],
            'location_id' => $data['locationId'] ?? null,
            'photos' => $data['photos'] ?? [],
            'requested_visibility' => $isViolation ? null : $data['requestedVisibility'],
            'is_anonymous' => $data['isAnonymous'],
            'incident_date' => $isViolation ? $data['incidentDate'] : null,
            'related_party' => $isViolation ? (($data['relatedParty'] ?? '') ?: null) : null,
        ]);

        session()->flash('status', 'Laporan berhasil dikirim. Tiketnya tersedia di daftar laporan saya.');
        $this->redirectRoute('reports.index', navigate: false);
    }

    public function render(): View
    {
        $reportType = ReportType::query()->where('code', $this->typeCode)->firstOrFail();
        $categories = $reportType->categories()->where('is_active', true)->orderBy('name')->get();
        $locations = Location::query()->where('is_active', true)->orderBy('name')->get();

        return view('livewire.pages.reports.create-form', compact('reportType', 'categories', 'locations'))
            ->title($reportType->name);
    }
}
