<?php

namespace App\Livewire\Pages\Settings;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['showPageHeading' => false])]
class Appearance extends Component
{
    public function render(): View
    {
        return view('livewire.pages.settings.appearance')->title('Pengaturan');
    }
}
