<?php

namespace App\Livewire\Components;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class NotificationBell extends Component
{
    #[Locked]
    public array $seenNotificationIds = [];

    public int $unreadCount = 0;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 401);

        $this->seenNotificationIds = $user->notifications()->latest()->limit(50)->pluck('id')->all();
        $this->unreadCount = $user->unreadNotifications()->count();
    }

    public function refreshNotifications(): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 401);

        $latestNotifications = $user->notifications()->latest()->limit(50)->get();
        $newNotifications = $latestNotifications->filter(fn ($notification): bool => $notification->read_at === null && ! in_array($notification->getKey(), $this->seenNotificationIds, true)
        );
        $this->seenNotificationIds = $latestNotifications->pluck('id')->all();
        $this->unreadCount = $user->unreadNotifications()->count();

        $this->dispatch('notification-count-updated', count: $this->unreadCount);

        if ($newNotifications->isNotEmpty()) {
            $this->dispatch('app-notification', message: $newNotifications->first()->data['message'] ?? 'Ada pembaruan laporan baru.', type: 'success');
        }
    }

    public function render(): View
    {
        return view('livewire.components.notification-bell');
    }
}
