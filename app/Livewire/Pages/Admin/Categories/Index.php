<?php

namespace App\Livewire\Pages\Admin\Categories;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\ReportType;
use App\Services\ActivityLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['description' => 'Kelola kategori laporan sesuai unit yang menjadi kewenanganmu. Kategori yang sudah dipakai tetap disimpan dan dapat dinonaktifkan.'])]
class Index extends Component
{
    use WithPagination;

    public string $newReportTypeId = '';

    public string $newName = '';

    /** @var array<int, array{name: string, is_active: bool}> */
    public array $categoryEdits = [];

    public function boot(): void
    {
        $user = auth()->user();
        abort_unless(in_array($user?->role, [UserRole::ADMIN_SARPRAS, UserRole::ADMIN_KEMAHASISWAAN, UserRole::SUPER_ADMIN], true), 403);
        abort_unless($user->is_active && session()->get('auth_version') === $user->auth_version && ! $user->must_change_password, 401);
        Gate::authorize('viewAny', Category::class);
    }

    public function createCategory(ActivityLogService $logs): void
    {
        $data = $this->validate([
            'newReportTypeId' => ['required', 'integer', 'exists:report_types,id'],
            'newName' => ['required', 'string', 'max:150'],
        ]);
        $reportType = ReportType::query()->findOrFail($data['newReportTypeId']);
        Gate::authorize('manageType', [Category::class, $reportType]);

        DB::transaction(function () use ($reportType, $data, $logs): void {
            $category = Category::query()->create([
                'report_type_id' => $reportType->id,
                'name' => $data['newName'],
                'is_active' => true,
            ]);
            $logs->record(auth()->user(), 'category_created', $category, [], [
                'report_type' => $reportType->code,
                'name' => $category->name,
            ]);
        });

        $this->reset(['newReportTypeId', 'newName']);
        $this->resetPage();
        $this->dispatch('app-notification', message: 'Kategori berhasil ditambahkan.', type: 'success');
    }

    public function updateCategory(int $categoryId, ActivityLogService $logs): void
    {
        $category = Category::query()->with('reportType')->findOrFail($categoryId);
        Gate::authorize('update', $category);
        $data = $this->validate([
            "categoryEdits.{$categoryId}.name" => ['required', 'string', 'max:150'],
            "categoryEdits.{$categoryId}.is_active" => ['required', 'boolean'],
        ]);
        $edit = $data['categoryEdits'][$categoryId];

        DB::transaction(function () use ($category, $edit, $logs): void {
            $oldValues = ['name' => $category->name, 'is_active' => $category->is_active];
            $category->update($edit);
            $logs->record(auth()->user(), 'category_updated', $category, $oldValues, [
                'name' => $category->name,
                'is_active' => $category->is_active,
            ]);
        });

        $this->dispatch('app-notification', message: 'Kategori berhasil diperbarui.', type: 'success');
    }

    public function deleteCategory(int $categoryId, ActivityLogService $logs): void
    {
        $category = Category::query()->findOrFail($categoryId);
        Gate::authorize('update', $category);
        $deleted = DB::transaction(function () use ($categoryId, $logs): bool {
            $lockedCategory = Category::query()->whereKey($categoryId)->lockForUpdate()->firstOrFail();
            if ($lockedCategory->reports()->exists()) {
                return false;
            }

            $logs->record(auth()->user(), 'category_deleted', $lockedCategory, ['name' => $lockedCategory->name]);
            $lockedCategory->delete();

            return true;
        });

        $this->dispatch('app-notification', message: $deleted
            ? 'Kategori berhasil dihapus.'
            : 'Kategori yang sudah dipakai laporan tidak dapat dihapus; nonaktifkan kategori.', type: $deleted ? 'success' : 'error');
        $this->resetPage();
    }

    public function render(): View
    {
        $reportTypes = ReportType::query()->orderBy('id')->get()
            ->filter(fn (ReportType $type): bool => Gate::allows('manageType', [Category::class, $type]));
        $categories = Category::query()
            ->with('reportType')
            ->withCount('reports')
            ->whereIn('report_type_id', $reportTypes->modelKeys())
            ->orderBy('report_type_id')
            ->orderBy('name')
            ->paginate(10)->onEachSide(1);

        foreach ($categories as $category) {
            $this->categoryEdits[$category->id] ??= [
                'name' => $category->name,
                'is_active' => $category->is_active,
            ];
        }

        return view('livewire.pages.admin.categories.index', compact('categories', 'reportTypes'))
            ->title('Kategori');
    }
}
