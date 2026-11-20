<?php

use App\Livewire\Components\NotificationBell;
use App\Livewire\Pages\Notifications\Index as NotificationsIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('marks a notification read and updates the unread counter without a page reload', function (): void {
    $user = User::factory()->create(['must_change_password' => false]);
    $notification = $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'report-status-updated',
        'data' => ['message' => 'Laporan sedang diproses.'],
    ]);

    Livewire::actingAs($user)
        ->test(NotificationsIndex::class)
        ->call('markRead', $notification->id)
        ->assertDispatched('app-notification')
        ->assertDispatched('notification-count-updated');

    expect($notification->fresh()->read_at)->not->toBeNull()
        ->and($user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('updates the unread badge and announces notifications that arrive while a page is open', function (): void {
    $user = User::factory()->create(['must_change_password' => false]);
    $component = Livewire::actingAs($user)->test(NotificationBell::class)
        ->assertSee('0');

    $notification = $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'report-submitted',
        'data' => ['message' => 'Laporan baru masuk dan menunggu peninjauan.'],
    ]);

    $component->call('refreshNotifications')
        ->assertSet('unreadCount', 1)
        ->assertDispatched('notification-count-updated', count: 1)
        ->assertDispatched('app-notification', message: 'Laporan baru masuk dan menunggu peninjauan.', type: 'success');

    $component->call('refreshNotifications')->assertNotDispatched('app-notification');
    expect($notification->fresh()->read_at)->toBeNull();
});
