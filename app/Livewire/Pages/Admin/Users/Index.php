<?php

namespace App\Livewire\Pages\Admin\Users;

use App\Enums\StudyProgram;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app', ['showPageHeading' => false])]
class Index extends Component
{
    public string $user_code = '';

    public string $name = '';

    public string $role = 'mahasiswa';

    public string $unit_or_prodi = '';

    public string $nim = '';

    public string $angkatan = '';

    public ?string $temporaryUserCode = null;

    public ?string $temporaryPassword = null;

    #[On('credentials-ready')]
    public function showCredentials(string $userCode, string $password): void
    {
        $this->temporaryUserCode = $userCode;
        $this->temporaryPassword = $password;
    }

    public function dismissCredentials(): void
    {
        $this->temporaryUserCode = null;
        $this->temporaryPassword = null;
    }

    public function boot(): void
    {
        $user = auth()->user();
        abort_unless($user?->role === UserRole::SUPER_ADMIN, 403);
        abort_unless($user->is_active && session()->get('auth_version') === $user->auth_version && ! $user->must_change_password, 401);
    }

    public function createUser(ActivityLogService $logs): void
    {
        $data = $this->validate([
            'user_code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,user_code'],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'unit_or_prodi' => [
                'required_if:role,mahasiswa', 'nullable', 'string', 'max:255',
                ...($this->role === UserRole::MAHASISWA->value ? [Rule::in(array_column(StudyProgram::cases(), 'value'))] : []),
            ],
            'nim' => ['exclude_unless:role,mahasiswa', 'required', 'string', 'max:30', 'unique:users,nim'],
            'angkatan' => ['exclude_unless:role,mahasiswa', 'required', 'integer', 'min:2000', 'max:'.(now()->year + 1)],
        ]);

        $password = Str::password(16);
        $user = DB::transaction(function () use ($data, $password, $logs): User {
            $createdUser = User::query()->create([...$data, 'password' => $password, 'is_active' => true, 'must_change_password' => true]);
            $logs->record(auth()->user(), 'user_created', $createdUser, [], ['role' => $createdUser->role->value]);

            return $createdUser;
        });

        $this->temporaryUserCode = $user->user_code;
        $this->temporaryPassword = $password;
        $this->reset(['user_code', 'name', 'role', 'unit_or_prodi', 'nim', 'angkatan']);
        session()->flash('status', 'Akun dibuat. Salin password awal sekarang; password tidak disimpan untuk ditampilkan kembali.');
    }

    public function render(): View
    {
        return view('livewire.pages.admin.users.index')
            ->title('Akun pengguna');
    }
}
