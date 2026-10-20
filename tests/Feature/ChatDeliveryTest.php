<?php

declare(strict_types=1);

use App\Actions\Chat\MarkChatConversationRead;
use App\Actions\Chat\SendChatMessage;
use App\Actions\Chat\SendChatTyping;
use App\Actions\Chat\StartDirectConversation;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Events\ChatMessageCreated;
use App\Events\ChatReadUpdated;
use App\Events\ChatTypingUpdated;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\assertDatabaseCount;

describe('Chat delivery boundaries', function (): void {
    it('refreshes permissions retained by a long running worker after role changes in another process', function (): void {
        [$actor, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        $event = new ChatReadUpdated($conversation->id, $actor->id, 1, [$actor->id, $recipient->id]);
        expect($event->broadcastOn())->toHaveCount(2);
        $workerRegistrar = resolve(PermissionRegistrar::class);

        app()->forgetInstance(PermissionRegistrar::class);
        $recipient->roles()->sole()->revokePermissionTo('chat.use');
        app()->instance(PermissionRegistrar::class, $workerRegistrar);

        expect(array_map(fn (PrivateChannel $channel): string => $channel->name, $event->broadcastOn()))
            ->toBe(['private-App.Models.User.'.$actor->id]);

        app()->forgetInstance(PermissionRegistrar::class);
        $recipient->roles()->sole()->givePermissionTo('chat.use');
        app()->instance(PermissionRegistrar::class, $workerRegistrar);

        expect($event->broadcastOn())->toHaveCount(2);
    });

    it('rechecks recipient access when queued events are delivered', function (string $revocation): void {
        [$actor, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        $message = ChatMessage::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $actor->id, 'sequence' => 1]);
        $ids = [$actor->id, $recipient->id];
        $events = [
            new ChatMessageCreated($message, $ids),
            new ChatReadUpdated($conversation->id, $actor->id, 1, $ids),
            new ChatTypingUpdated($conversation->id, $actor->id, true, now()->addSeconds(4)->toISOString(), $ids),
        ];
        foreach ($events as $event) {
            expect($event->broadcastOn())->toHaveCount(2);
        }

        match ($revocation) {
            'disabled' => $recipient->forceFill(['status' => UserStatus::Disabled])->save(),
            'permission' => $recipient->syncRoles([]),
            'membership' => $conversation->participants()->where('user_id', $recipient->id)->delete(),
        };

        foreach ($events as $event) {
            expect(array_map(fn (PrivateChannel $channel): string => $channel->name, $event->broadcastOn()))
                ->toBe(['private-App.Models.User.'.$actor->id]);
        }
    })->with(['disabled', 'permission', 'membership']);

    it('preserves superadmin delivery and excludes unrelated user identifiers', function (): void {
        [$actor, $recipient] = createChatUsers();
        $recipient->syncRoles(Role::SuperAdmin);
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        [$outsider] = createChatUsers();
        $event = new ChatReadUpdated($conversation->id, $actor->id, 1, [$recipient->id, $outsider->id]);

        expect(array_map(fn (PrivateChannel $channel): string => $channel->name, $event->broadcastOn()))
            ->toBe(['private-App.Models.User.'.$recipient->id]);
    });

    it('discards message, read and typing broadcasts when the enclosing transaction rolls back', function (): void {
        [$actor, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);

        DB::beginTransaction();
        try {
            resolve(SendChatMessage::class)->handle($actor, $conversation, ['body' => 'Rolled back', 'client_message_id' => (string) Str::uuid()]);
            resolve(MarkChatConversationRead::class)->handle($recipient, $conversation, 1);
            resolve(SendChatTyping::class)->handle($actor, $conversation, true);
            assertDatabaseCount('jobs', 0);
        } finally {
            DB::rollBack();
        }

        assertDatabaseCount('jobs', 0);
        assertDatabaseCount('chat_messages', 0);
        expect($conversation->fresh()->last_message_sequence)->toBe(0)
            ->and($conversation->participants()->where('user_id', $recipient->id)->firstOrFail()->last_read_sequence)->toBe(0);
    });

    it('queues committed events once on chat without consuming the notification queue', function (): void {
        [$actor, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        $data = ['body' => 'Committed', 'client_message_id' => (string) Str::uuid()];

        DB::transaction(function () use ($actor, $recipient, $conversation, $data): void {
            resolve(SendChatMessage::class)->handle($actor, $conversation, $data);
            resolve(MarkChatConversationRead::class)->handle($recipient, $conversation, 1);
            resolve(SendChatTyping::class)->handle($actor, $conversation, true);
            assertDatabaseCount('jobs', 0);
        });
        resolve(SendChatMessage::class)->handle($actor, $conversation, $data);

        assertDatabaseCount('jobs', 3);
        expect(DB::table('jobs')->pluck('queue')->all())->toBe(['chat', 'chat', 'chat']);
    });
});
