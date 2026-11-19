<?php

namespace App\Livewire\Components\Admin;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class UserTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public string $statusFilter = '';

    public function boot(): void
    {
        $user = auth()->user();

        abort_unless($user?->role === UserRole::SUPER_ADMIN, 403);
        abort_unless(
            $user->is_active
                && session()->get('auth_version') === $user->auth_version
                && ! $user->must_change_password,
            401,
        );
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'roleFilter', 'statusFilter']);
        $this->resetPage();
    }

    public function users(): LengthAwarePaginator
    {
        Validator::make([
            'search' => $this->search,
            'roleFilter' => $this->roleFilter,
            'statusFilter' => $this->statusFilter,
        ], [
            'search' => ['string', 'max:100'],
            'roleFilter' => ['nullable', Rule::in(array_column(UserRole::cases(), 'value'))],
            'statusFilter' => ['nullable', Rule::in(['aktif', 'nonaktif'])],
        ])->validate();

        $search = trim($this->search);

        return User::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('user_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            }))
            ->when($this->roleFilter !== '', fn ($query) => $query->where('role', $this->roleFilter))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('is_active', $this->statusFilter === 'aktif'))
            ->orderBy('user_code')
            ->paginate(20);
    }

    public function resetPassword(int $userId, ActivityLogService $logs): void
    {
        $user = User::query()->findOrFail($userId);
        abort_if(auth()->user()->is($user), 422, 'Super Admin tidak dapat mereset password akunnya sendiri dari halaman ini.');
        $password = Str::password(16);

        DB::transaction(function () use ($user, $password, $logs): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'must_change_password' => true,
                'auth_version' => $user->auth_version + 1,
            ])->save();
            $logs->record(auth()->user(), 'user_password_reset', $user, [], ['must_change_password' => true]);
        });

        $this->dispatch('app-notification', message: 'Password direset. Salin password awal sekarang.', type: 'success');
        $this->dispatch('credentials-ready', userCode: $user->user_code, password: $password);
    }

    public function toggleActive(int $userId, ActivityLogService $logs): void
    {
        $user = User::query()->findOrFail($userId);
        abort_if(auth()->user()->is($user), 422, 'Super Admin tidak dapat mengubah status akunnya sendiri.');
        $wasActive = $user->is_active;

        DB::transaction(function () use ($user, $wasActive, $logs): void {
            $user->forceFill(['is_active' => ! $wasActive, 'auth_version' => $user->auth_version + 1])->save();
            $logs->record(auth()->user(), $wasActive ? 'user_deactivated' : 'user_activated', $user,
                ['is_active' => $wasActive], ['is_active' => ! $wasActive]);
        });

        $this->dispatch('app-notification', message: $wasActive ? 'Akun dinonaktifkan.' : 'Akun berhasil diaktifkan kembali.', type: 'success');
    }

    public function render()
    {
        return view('livewire.components.admin.user-table', [
            'users' => $this->users(),
            'totalUsersCount' => User::query()->count(),
        ]);
    }
}
