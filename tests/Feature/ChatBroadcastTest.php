<?php

declare(strict_types=1);

use App\Actions\Chat\MarkChatConversationRead;
use App\Actions\Chat\SendChatMessage;
use App\Actions\Chat\StartDirectConversation;
use App\Events\ChatMessageCreated;
use App\Events\ChatReadUpdated;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

describe('Chat broadcasting', function (): void {
    it('broadcasts a new message after commit with a minimal payload on both private user channels', function (): void {
        [$actor, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        Event::fake([ChatMessageCreated::class]);

        $message = resolve(SendChatMessage::class)->handle($actor, $conversation, [
            'body' => 'Realtime hello',
            'client_message_id' => (string) Str::uuid(),
        ]);

        Event::assertDispatched(ChatMessageCreated::class, function (ChatMessageCreated $event) use ($actor, $conversation, $message, $recipient): bool {
            $payload = $event->broadcastWith();

            expect($event)->toBeInstanceOf(ShouldBroadcast::class)
                ->and($event)->toBeInstanceOf(ShouldDispatchAfterCommit::class)
                ->and($event->connection)->toBe('realtime')
                ->and($event->queue)->toBe('chat')
                ->and($event->message->is($message))->toBeTrue()
                ->and($event->userIds)->toEqualCanonicalizing([$actor->id, $recipient->id])
                ->and(array_map(fn (PrivateChannel $channel): string => $channel->name, $event->broadcastOn()))
                ->toEqualCanonicalizing(['private-App.Models.User.'.$actor->id, 'private-App.Models.User.'.$recipient->id])
                ->and(array_keys($payload))->toBe(['id', 'conversation_id', 'sender_id', 'sequence', 'client_message_id', 'body', 'created_at'])
                ->and($payload)->toMatchArray([
                    'id' => $message->id,
                    'conversation_id' => $conversation->id,
                    'sender_id' => $actor->id,
                    'sequence' => 1,
                    'client_message_id' => $message->client_message_id,
                    'body' => 'Realtime hello',
                    'created_at' => $message->created_at?->toISOString(),
                ])
                ->and(json_encode($payload))->not->toContain($actor->email)
                ->and(json_encode($payload))->not->toContain($recipient->email);

            return true;
        });
    });

    it('does not broadcast retries that return the canonical message', function (): void {
        [$actor, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        $data = ['body' => 'Only once', 'client_message_id' => (string) Str::uuid()];
        Event::fake([ChatMessageCreated::class]);

        $first = resolve(SendChatMessage::class)->handle($actor, $conversation, $data);
        $retry = resolve(SendChatMessage::class)->handle($actor, $conversation, $data);

        expect($retry->id)->toBe($first->id);
        Event::assertDispatchedTimes(ChatMessageCreated::class, 1);
    });

    it('does not broadcast messages rejected by validation', function (): void {
        [$actor, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        Event::fake([ChatMessageCreated::class]);

        expect(fn () => resolve(SendChatMessage::class)->handle($actor, $conversation, [
            'body' => '   ',
            'client_message_id' => (string) Str::uuid(),
        ]))->toThrow(ValidationException::class);
        Event::assertNotDispatched(ChatMessageCreated::class);
    });

    it('broadcasts monotonic read cursor changes to both participants', function (): void {
        [$actor, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        foreach ([1, 2] as $sequence) {
            ChatMessage::factory()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $recipient->id,
                'sequence' => $sequence,
            ]);
        }
        $conversation->update(['last_message_sequence' => 2]);
        Event::fake([ChatReadUpdated::class]);
        $markRead = resolve(MarkChatConversationRead::class);

        $markRead->handle($actor, $conversation, 1);
        $markRead->handle($actor, $conversation, 1);
        $markRead->handle($actor, $conversation, 2);

        Event::assertDispatchedTimes(ChatReadUpdated::class, 2);
        Event::assertDispatched(ChatReadUpdated::class, function (ChatReadUpdated $event) use ($actor, $conversation, $recipient): bool {
            if ($event->sequence !== 2) {
                return false;
            }

            expect($event)->toBeInstanceOf(ShouldBroadcast::class)
                ->and($event)->toBeInstanceOf(ShouldDispatchAfterCommit::class)
                ->and($event->connection)->toBe('realtime')
                ->and($event->queue)->toBe('chat')
                ->and($event->userIds)->toEqualCanonicalizing([$actor->id, $recipient->id])
                ->and(array_map(fn (PrivateChannel $channel): string => $channel->name, $event->broadcastOn()))
                ->toEqualCanonicalizing(['private-App.Models.User.'.$actor->id, 'private-App.Models.User.'.$recipient->id])
                ->and($event->broadcastWith())->toBe([
                    'conversation_id' => $conversation->id,
                    'user_id' => $actor->id,
                    'last_read_sequence' => 2,
                ]);

            return true;
        });
    });

    it('queues broadcasts on the realtime connection after database commits', function (): void {
        expect(config('queue.connections.realtime.after_commit'))->toBeTrue()
            ->and(config('queue.connections.realtime.queue'))->toBe('notifications');
    });
});
