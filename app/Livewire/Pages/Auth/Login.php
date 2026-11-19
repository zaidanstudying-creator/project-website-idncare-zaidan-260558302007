<?php

namespace App\Livewire\Pages\Auth;

use App\Enums\UserRole;
use App\Models\SiteSetting;
use App\Services\ActivityLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public', ['hidePublicNav' => true, 'bodyClass' => 'login-body'])]
class Login extends Component
{
    public string $userCode = '';

    public string $password = '';

    public bool $remember = false;

    public function authenticate(ActivityLogService $activityLogs): void
    {
        $data = $this->validate([
            'userCode' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ], [
            'userCode.required' => 'ID pengguna wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);
        $key = Str::transliterate(Str::lower($data['userCode']).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'userCode' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        if (! Auth::attempt([
            'user_code' => $data['userCode'],
            'password' => $data['password'],
            'is_active' => true,
        ], $data['remember'] ?? false)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'userCode' => 'ID pengguna atau password tidak sesuai.',
            ]);
        }

        RateLimiter::clear($key);
        $user = Auth::user();

        if (SiteSetting::maintenanceEnabled() && $user->role === UserRole::MAHASISWA) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            session()->flash('error', 'Login sementara tidak tersedia karena website sedang maintenance. Silakan coba lagi nanti.');
            $this->redirectRoute('login');

            return;
        }

        session()->regenerate();
        session()->put('auth_version', $user->auth_version);
        $activityLogs->record($user, 'login');

        $dashboardRoute = match ($user->role) {
            UserRole::MAHASISWA => 'dashboard.mahasiswa',
            UserRole::ADMIN_SARPRAS => 'dashboard.admin-sarpras',
            UserRole::ADMIN_KEMAHASISWAAN => 'dashboard.admin-kemahasiswaan',
            UserRole::SUPER_ADMIN => 'dashboard.super-admin',
        };

        $this->redirectRoute($user->must_change_password ? 'password.change' : $dashboardRoute);
    }

    public function render(): View
    {
        return view('livewire.pages.auth.login')->title('Masuk');
    }
}
