<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\ApplicationNotification;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\withUnencryptedCookie;

describe('Notification center', function (): void {
    it('requires authentication for notification pages and mutations', function (): void {
        get(route('notifications.index'))->assertRedirect(route('login'));
        patch(route('notifications.read-all'))->assertRedirect(route('login'));
        patch(route('notifications.read', '01999abc-1111-7111-8111-111111111111'))->assertRedirect(route('login'));
    });

    it('lists only personal notifications and shares the unread count', function (): void {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $user->notify(new ApplicationNotification('account_updated'));
        $other->notify(new ApplicationNotification('invitation_queued', 'Private recipient'));

        actingAs($user)->get(route('notifications.index'))
            ->assertInertia(fn (Assert $page): Assert => $page->component('notifications/Index')->has('notifications.data', 1)
                ->where('notificationSummary.unread_count', 1)->where('status', 'all')
                ->where('notifications.data.0.kind', 'account_updated')
                ->where('notifications.data.0.read_at', null)
                ->where('notifications.data.0.message', 'Account details or access have been updated.'))
            ->assertDontSee('Private recipient');
    });

    it('filters read and unread notifications and retains filters during pagination', function (string $status, int $total): void {
        $user = User::factory()->create();
        $user->notify(new ApplicationNotification('account_created'));
        $user->notify(new ApplicationNotification('account_updated'));
        $user->notifications()->first()->markAsRead();

        actingAs($user)->get(route('notifications.index', ['status' => $status]))
            ->assertInertia(fn (Assert $page): Assert => $page->where('notifications.total', $total)->where('status', $status));
    })->with(['all' => ['all', 2], 'unread' => ['unread', 1], 'read' => ['read', 1]]);

    it('paginates notifications without exposing another account', function (): void {
        $user = User::factory()->create();
        foreach (range(1, 21) as $number) {
            $user->notify(new ApplicationNotification('invitation_queued', 'Recipient '.$number));
        }

        actingAs($user)->get(route('notifications.index', ['status' => 'unread', 'page' => 2]))
            ->assertInertia(fn (Assert $page): Assert => $page->has('notifications.data', 1)->where('notifications.current_page', 2)->where('notifications.total', 21));
    });

    it('marks a personal notification read and remains idempotent', function (): void {
        $user = User::factory()->create();
        $user->notify(new ApplicationNotification('account_updated'));
        $notification = $user->notifications()->sole();

        actingAs($user)->from(route('notifications.index'))->patch(route('notifications.read', $notification->id))
            ->assertRedirect(route('notifications.index'));
        $readAt = $notification->refresh()->read_at;
        expect($readAt)->not->toBeNull()->and($user->unreadNotifications()->count())->toBe(0);

        patch(route('notifications.read', $notification->id))->assertRedirect();
        expect($notification->refresh()->read_at->equalTo($readAt))->toBeTrue();
        get(route('notifications.index', ['status' => 'read']))->assertInertia(fn (Assert $page): Assert => $page->has('notifications.data', 1)->where('notificationSummary.unread_count', 0));
    });

    it('returns 404 for notifications belonging to another user', function (): void {
        $owner = User::factory()->create();
        $owner->notify(new ApplicationNotification('account_updated'));
        $notification = $owner->notifications()->sole();

        actingAs(User::factory()->create())->patch(route('notifications.read', $notification->id))->assertNotFound();
        expect($notification->refresh()->read_at)->toBeNull();
        patch(route('notifications.read', '01999abc-1111-7111-8111-111111111111'))->assertNotFound();
        patch(route('notifications.read', 'invalid'))->assertNotFound();
    });

    it('marks all personal notifications read without changing another account', function (): void {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $user->notify(new ApplicationNotification('account_created'));
        $user->notify(new ApplicationNotification('permissions_updated'));
        $other->notify(new ApplicationNotification('account_updated'));

        actingAs($user)->from(route('notifications.index'))->patch(route('notifications.read-all'))->assertRedirect(route('notifications.index'));
        expect($user->unreadNotifications()->count())->toBe(0)->and($other->unreadNotifications()->count())->toBe(1);
        patch(route('notifications.read-all'))->assertRedirect();
    });

    it('rejects invalid filters', function (): void {
        actingAs(User::factory()->create())->get(route('notifications.index', ['status' => 'invalid']))->assertSessionHasErrors('status');
    });

    it('renders stored notifications in the current locale', function (): void {
        $user = User::factory()->create();
        $user->notify(new ApplicationNotification('invitation_queued', 'Recipient'));

        actingAs($user);
        withUnencryptedCookie('locale', 'id')->get(route('notifications.index'))
            ->assertInertia(fn (Assert $page): Assert => $page->where('notifications.data.0.message', 'Undangan untuk Recipient telah masuk antrean pengiriman.'));
    });

    it('rolls back database notifications with their transaction', function (): void {
        $user = User::factory()->create();
        DB::beginTransaction();
        $user->notify(new ApplicationNotification('account_created'));
        DB::rollBack();

        expect($user->notifications()->count())->toBe(0);
    });
});
