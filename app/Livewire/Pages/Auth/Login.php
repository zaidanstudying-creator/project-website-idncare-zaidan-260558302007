<?php

namespace App\Livewire\Pages\Auth;

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
        ]);
        $key = Str::transliterate(Str::lower($data['userCode']).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'userCode' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        $credentials = [
            'user_code' => $data['userCode'],
            'password' => $data['password'],
            'is_active' => true,
        ];

        if (! Auth::attempt($credentials, $data['remember'])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['userCode' => 'ID pengguna atau password tidak sesuai.']);
        }

        RateLimiter::clear($key);
        request()->session()->regenerate();
        request()->session()->put('auth_version', auth()->user()->auth_version);
        $activityLogs->record(auth()->user(), 'login');

        if (auth()->user()->must_change_password) {
            $this->redirectRoute('password.change', navigate: false);

            return;
        }

        $this->redirectIntended(route('dashboard'), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.pages.auth.login')->title('Masuk');
    }
}
