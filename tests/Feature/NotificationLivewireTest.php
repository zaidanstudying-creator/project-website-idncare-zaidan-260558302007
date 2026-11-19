<?php

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
