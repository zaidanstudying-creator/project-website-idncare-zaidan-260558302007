<?php

namespace App\Livewire\Pages\Admin\Users;

use App\Enums\StudyProgram;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app', ['showPageHeading' => false])]
class EditAccount extends Component
{
    #[Locked]
    public int $userId;

    public string $name = '';

    public string $unit_or_prodi = '';

    public string $nim = '';

    public string $angkatan = '';

    public function mount(User $user): void
    {
        abort_if(auth()->user()->is($user), 403);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->unit_or_prodi = $user->unit_or_prodi ?? '';
        $this->nim = $user->nim ?? '';
        $this->angkatan = (string) $user->angkatan;
    }

    public function boot(): void
    {
        $user = auth()->user();
        abort_unless($user?->role === UserRole::SUPER_ADMIN, 403);
        abort_unless($user->is_active && session()->get('auth_version') === $user->auth_version && ! $user->must_change_password, 401);
    }

    public function saveAccount(ActivityLogService $logs): void
    {
        $account = $this->account();
        $isStudent = $account->role === UserRole::MAHASISWA;
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'unit_or_prodi' => $isStudent
                ? ['required', Rule::in(array_column(StudyProgram::cases(), 'value'))]
                : ['nullable', 'string', 'max:255'],
        ];
        if ($isStudent) {
            $rules['nim'] = ['required', 'string', 'max:30', Rule::unique('users', 'nim')->ignore($account->id)];
            $rules['angkatan'] = ['required', 'integer', 'min:2000', 'max:'.(now()->year + 1)];
        }

        $data = $this->validate($rules);
        if (! $isStudent) {
            $data['unit_or_prodi'] = $data['unit_or_prodi'] ?: null;
        }

        $changed = DB::transaction(function () use ($account, $data, $isStudent, $logs): bool {
            $lockedAccount = User::query()->lockForUpdate()->findOrFail($account->id);
            abort_if(auth()->user()->is($lockedAccount), 403);
            abort_unless(($lockedAccount->role === UserRole::MAHASISWA) === $isStudent, 404);
            $lockedAccount->fill($data);
            $changes = $lockedAccount->getDirty();

            if ($changes === []) {
                return false;
            }

            $oldValues = array_intersect_key($lockedAccount->getOriginal(), $changes);
            $newValues = array_intersect_key($lockedAccount->getAttributes(), $changes);
            $lockedAccount->save();
            $logs->record(auth()->user(), $isStudent ? 'student_account_updated' : 'admin_account_updated', $lockedAccount, $oldValues, $newValues);

            return true;
        });

        $subject = $isStudent ? 'mahasiswa' : 'admin';
        $this->dispatch('app-notification', message: $changed
            ? "Informasi akun {$subject} berhasil diperbarui."
            : 'Tidak ada perubahan pada informasi akun.', type: 'success');
        $this->redirectRoute('admin.users.index', navigate: false);
    }

    public function render(): View
    {
        $account = $this->account();
        $isStudent = $account->role === UserRole::MAHASISWA;

        return view('livewire.pages.admin.users.edit', compact('account', 'isStudent'))
            ->title('Edit informasi akun');
    }

    private function account(): User
    {
        $account = User::query()->findOrFail($this->userId);
        abort_if(auth()->user()->is($account), 403);

        return $account;
    }
}
