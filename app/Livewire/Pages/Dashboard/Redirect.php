<?php

namespace App\Livewire\Pages\Dashboard;

use App\Enums\UserRole;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Redirect extends Component
{
    public function mount(): void
    {
        $this->redirectRoute(match (auth()->user()->role) {
            UserRole::MAHASISWA => 'dashboard.mahasiswa',
            UserRole::ADMIN_SARPRAS => 'dashboard.admin-sarpras',
            UserRole::ADMIN_KEMAHASISWAAN => 'dashboard.admin-kemahasiswaan',
            UserRole::SUPER_ADMIN => 'dashboard.super-admin',
        });
    }

    public function render(): View
    {
        return view('livewire.pages.dashboard.redirect');
    }
}
