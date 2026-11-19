<?php

namespace App\Livewire\Components\Auth;

use App\Services\ActivityLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Logout extends Component
{
    public function logout(ActivityLogService $activityLogs): void
    {
        $activityLogs->record(auth()->user(), 'logout');
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirectRoute('login', navigate: false);
    }

    public function render(): View
    {
        return view('livewire.components.auth.logout');
    }
}
