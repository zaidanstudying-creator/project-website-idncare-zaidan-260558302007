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
use Livewire\Component;

#[Layout('layouts.app', ['showPageHeading' => false])]
class EditStudent extends Component
{
    public User $student;

    public string $name = '';

    public string $unit_or_prodi = '';

    public string $nim = '';

    public string $angkatan = '';

    public function mount(User $user): void
    {
        abort_unless($user->role === UserRole::MAHASISWA, 404);
        abort_if(auth()->user()->is($user), 403);
        $this->student = $user;
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

    public function saveStudent(ActivityLogService $logs): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit_or_prodi' => ['required', Rule::in(array_column(StudyProgram::cases(), 'value'))],
            'nim' => ['required', 'string', 'max:30', Rule::unique('users', 'nim')->ignore($this->student->id)],
            'angkatan' => ['required', 'integer', 'min:2000', 'max:'.(now()->year + 1)],
        ]);

        $changed = DB::transaction(function () use ($data, $logs): bool {
            $student = User::query()->findOrFail($this->student->id);
            abort_unless($student->role === UserRole::MAHASISWA, 404);
            abort_if(auth()->user()->is($student), 403);
            $student->fill($data);
            $changes = $student->getDirty();

            if ($changes === []) {
                return false;
            }

            $oldValues = array_intersect_key($student->getOriginal(), $changes);
            $newValues = array_intersect_key($student->getAttributes(), $changes);
            $student->save();
            $logs->record(auth()->user(), 'student_account_updated', $student, $oldValues, $newValues);

            return true;
        });

        $this->dispatch('app-notification', message: $changed
            ? 'Informasi akun mahasiswa berhasil diperbarui.'
            : 'Tidak ada perubahan pada informasi akun.', type: 'success');
        $this->redirectRoute('admin.users.index', navigate: false);
    }

    public function render(): View
    {
        return view('livewire.pages.admin.users.edit')->title('Edit informasi mahasiswa');
    }
}
