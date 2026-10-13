<?php

declare(strict_types=1);

use App\Actions\Notification\MarkAllNotificationsRead;
use App\Actions\Notification\MarkNotificationRead;
use App\Enums\UserStatus;
use App\Events\NotificationRead;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

describe('Private realtime notifications', function (): void {
    beforeEach(function (): void {
        config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => 'test-key', 'broadcasting.connections.reverb.secret' => 'test-secret', 'broadcasting.connections.reverb.app_id' => 'test-app']);
        require base_path('routes/channels.php');
    });

    it('authorizes only the active channel owner', function (): void {
        $user = User::factory()->create();
        $data = ['socket_id' => '123.456', 'channel_name' => 'private-App.Models.User.'.$user->id];
        postJson(action([BroadcastController::class, 'authenticate']), $data)->assertForbidden();
        actingAs($user)->postJson(action([BroadcastController::class, 'authenticate']), $data)->assertOk()->assertJsonStructure(['auth']);
        postJson(action([BroadcastController::class, 'authenticate']), [...$data, 'channel_name' => 'private-App.Models.User.'.User::factory()->create()->id])->assertForbidden();
        postJson(action([BroadcastController::class, 'authenticate']), [...$data, 'channel_name' => 'private-unknown'])->assertForbidden();
        $user->forceFill(['status' => UserStatus::Disabled])->save();
        postJson(action([BroadcastController::class, 'authenticate']), $data)->assertRedirect(route('login'));
    });

    it('queues a minimal broadcast only after the notification transaction commits', function (): void {
        $user = User::factory()->create();
        DB::transaction(function () use ($user): void {
            $user->notify(new ApplicationNotification('account_updated', 'Private subject'));
            expect(DB::table('jobs')->where('queue', 'notifications')->count())->toBe(0);
        });
        $payload = json_decode(DB::table('jobs')->where('queue', 'notifications')->sole()->payload, true);
        $job = unserialize($payload['data']['command']);
        expect($job)->toBeInstanceOf(BroadcastEvent::class)
            ->and($job->event)->toBeInstanceOf(BroadcastNotificationCreated::class)
            ->and($job->event->broadcastOn()[0]->name)->toBe('private-App.Models.User.'.$user->id)
            ->and($job->event->broadcastWith())->toMatchArray(['kind' => 'account_updated', 'id' => $user->notifications()->sole()->id])
            ->and($job->event->broadcastWith())->not->toHaveKey('subject');
    });

    it('discards broadcasts when notification creation rolls back', function (): void {
        $user = User::factory()->create();
        DB::beginTransaction();
        $user->notify(new ApplicationNotification('account_updated'));
        DB::rollBack();

        expect($user->notifications()->count())->toBe(0)
            ->and(DB::table('jobs')->where('queue', 'notifications')->count())->toBe(0);
    });

    it('broadcasts changed read state once and keeps repeated mutations silent', function (bool $all): void {
        $user = User::factory()->create();
        $user->notify(new ApplicationNotification('account_updated'));
        $id = $user->notifications()->sole()->id;
        Event::fake([NotificationRead::class]);
        $read = fn () => $all
            ? resolve(MarkAllNotificationsRead::class)->handle($user)
            : resolve(MarkNotificationRead::class)->handle($user, $id);
        DB::transaction($read);
        DB::transaction($read);
        Event::assertDispatchedTimes(NotificationRead::class, 1);
        Event::assertDispatched(NotificationRead::class, fn (NotificationRead $event): bool => $event->broadcastOn()[0]->name === 'private-App.Models.User.'.$user->id && $event->broadcastWith() === []);
    })->with([false, true]);

    it('does not broadcast read state from rolled back mutations', function (): void {
        $user = User::factory()->create();
        $user->notify(new ApplicationNotification('account_updated'));
        Event::fake([NotificationRead::class]);
        DB::beginTransaction();

        resolve(MarkAllNotificationsRead::class)->handle($user);

        DB::rollBack();
        Event::assertNotDispatched(NotificationRead::class);

        expect($user->unreadNotifications()->count())->toBe(1);
    });
});
