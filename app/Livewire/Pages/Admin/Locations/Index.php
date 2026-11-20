<?php

namespace App\Livewire\Pages\Admin\Locations;

use App\Enums\UserRole;
use App\Models\Location;
use App\Services\ActivityLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['description' => 'Kelola daftar lokasi kampus satu tingkat. Lokasi yang sudah digunakan tidak dihapus; nonaktifkan agar tidak dipilih untuk laporan baru.'])]
class Index extends Component
{
    use WithPagination;

    public string $newName = '';

    /** @var array<int, array{name: string}> */
    public array $locationEdits = [];

    public function boot(): void
    {
        $user = auth()->user();
        abort_unless(in_array($user?->role, [UserRole::ADMIN_SARPRAS, UserRole::ADMIN_KEMAHASISWAAN, UserRole::SUPER_ADMIN], true), 403);
        abort_unless($user->is_active && session()->get('auth_version') === $user->auth_version && ! $user->must_change_password, 401);
        Gate::authorize('viewAny', Location::class);
    }

    public function createLocation(ActivityLogService $logs): void
    {
        Gate::authorize('create', Location::class);
        $data = $this->validate(['newName' => ['required', 'string', 'max:150']]);

        DB::transaction(function () use ($data, $logs): void {
            $location = Location::query()->create(['name' => $data['newName'], 'is_active' => true]);
            $logs->record(auth()->user(), 'location_created', $location, [], ['name' => $location->name]);
        });

        $this->reset('newName');
        $this->resetPage();
        $this->dispatch('app-notification', message: 'Lokasi berhasil ditambahkan.', type: 'success');
    }

    public function updateLocation(int $locationId, ActivityLogService $logs): void
    {
        $location = Location::query()->findOrFail($locationId);
        Gate::authorize('update', $location);
        $data = $this->validate(["locationEdits.{$locationId}.name" => ['required', 'string', 'max:150']]);
        $edit = $data['locationEdits'][$locationId];

        DB::transaction(function () use ($location, $edit, $logs): void {
            $oldValues = ['name' => $location->name];
            $location->update($edit);
            $logs->record(auth()->user(), 'location_updated', $location, $oldValues, ['name' => $location->name]);
        });

        $this->dispatch('app-notification', message: 'Lokasi berhasil diperbarui.', type: 'success');
    }

    public function toggleLocationActive(int $locationId, ActivityLogService $logs): void
    {
        $location = Location::query()->findOrFail($locationId);
        Gate::authorize('update', $location);

        $isActive = DB::transaction(function () use ($locationId, $logs): bool {
            $lockedLocation = Location::query()->whereKey($locationId)->lockForUpdate()->firstOrFail();
            $wasActive = $lockedLocation->is_active;
            $isActive = ! $wasActive;
            $lockedLocation->update(['is_active' => $isActive]);
            $logs->record(auth()->user(), 'location_updated', $lockedLocation,
                ['is_active' => $wasActive], ['is_active' => $isActive]);

            return $isActive;
        });

        $this->dispatch('app-notification', message: $isActive
            ? 'Lokasi berhasil diaktifkan.'
            : 'Lokasi berhasil dinonaktifkan.', type: 'success');
    }

    public function deleteLocation(int $locationId, ActivityLogService $logs): void
    {
        $location = Location::query()->findOrFail($locationId);
        Gate::authorize('update', $location);
        $deleted = DB::transaction(function () use ($locationId, $logs): bool {
            $lockedLocation = Location::query()->whereKey($locationId)->lockForUpdate()->firstOrFail();
            if ($lockedLocation->reports()->exists()) {
                return false;
            }

            $logs->record(auth()->user(), 'location_deleted', $lockedLocation, ['name' => $lockedLocation->name]);
            $lockedLocation->delete();

            return true;
        });

        $this->dispatch('app-notification', message: $deleted
            ? 'Lokasi berhasil dihapus.'
            : 'Lokasi yang sudah dipakai laporan tidak dapat dihapus; nonaktifkan lokasinya.', type: $deleted ? 'success' : 'error');
        $this->resetPage();
    }

    public function render(): View
    {
        $locations = Location::query()->withCount('reports')->orderBy('name')->paginate(10)->onEachSide(1);
        foreach ($locations as $location) {
            $this->locationEdits[$location->id] ??= [
                'name' => $location->name,
            ];
        }

        return view('livewire.pages.admin.locations.index', compact('locations'))
            ->title('Lokasi');
    }
}
