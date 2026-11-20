<?php

namespace App\Livewire\Pages\Notifications;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['showPageHeading' => false])]
class Index extends Component
{
    use WithPagination;

    public function markRead(string $notificationId): void
    {
        $notification = auth()->user()->notifications()->whereKey($notificationId)->firstOrFail();
        $notification->markAsRead();
        $this->dispatch('notification-count-updated', count: auth()->user()->unreadNotifications()->count());
        $this->dispatch('app-notification', message: 'Notifikasi ditandai sudah dibaca.', type: 'success');
    }

    public function render(): View
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(20)->onEachSide(1);

        return view('livewire.pages.notifications.index', compact('notifications'))->title('Notifikasi');
    }
}
