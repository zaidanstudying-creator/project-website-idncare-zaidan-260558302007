<?php

namespace App\Livewire\Pages\Auth;

use App\Services\ActivityLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['limitedNavigation' => true, 'showPageHeading' => false])]
class PasswordChange extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function savePassword(ActivityLogService $activityLogs): void
    {
        $data = $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'password.confirmed' => 'Konfirmasi password baru harus sama persis dengan password baru.',
            'password.different' => 'Password baru harus berbeda dari password saat ini.',
            'password.min' => 'Password baru minimal 8 karakter.',
        ]);

        $user = auth()->user();
        $mustChangePassword = $user->must_change_password;
        DB::transaction(function () use ($user, $data, $activityLogs, $mustChangePassword): void {
            $user->password = $data['password'];
            $user->must_change_password = false;
            $user->save();
            $activityLogs->record($user, 'password_changed', $user,
                ['must_change_password' => $mustChangePassword], ['must_change_password' => false]);
        });

        session()->regenerate();
        $this->redirectRoute('dashboard', navigate: false);
    }

    public function render(): View
    {
        return view('livewire.pages.auth.password-change')->title('Ganti password');
    }
}
