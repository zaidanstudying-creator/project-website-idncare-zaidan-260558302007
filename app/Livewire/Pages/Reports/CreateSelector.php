<?php

namespace App\Livewire\Pages\Reports;

use App\Enums\UserRole;
use App\Models\ReportType;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app', ['showPageHeading' => false])]
class CreateSelector extends Component
{
    #[Url(as: 'type')]
    public string $selectedTypeCode = '';

    public function render(): View
    {
        abort_unless(auth()->user()?->role === UserRole::MAHASISWA, 403);
        $types = ReportType::query()->orderBy('id')->get();
        abort_if($types->isEmpty(), 404);
        $selectedType = $types->firstWhere('code', $this->selectedTypeCode)
            ?? $types->firstWhere('code', ReportType::FACILITAS);

        return view('livewire.pages.reports.create', compact('types', 'selectedType'))
            ->title('Buat laporan');
    }
}
