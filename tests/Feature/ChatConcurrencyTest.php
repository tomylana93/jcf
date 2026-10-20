<?php

declare(strict_types=1);

use App\Actions\Chat\SendChatMessage;
use App\Actions\Chat\StartDirectConversation;
use App\Models\ChatMessage;
use Illuminate\Support\Str;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

describe('PostgreSQL chat concurrency', function (): void {
    it('creates one direct conversation when both users start it concurrently', function (): void {
        withChatConcurrencyDatabase(function (): void {
            [$sender, $recipient] = createChatUsers();

            $results = runConcurrentChatOperations([
                ['action' => 'start', 'actor' => $sender->id, 'recipient' => $recipient->id],
                ['action' => 'start', 'actor' => $recipient->id, 'recipient' => $sender->id],
            ]);

            expect($results[0])->toBe($results[1]);
            assertDatabaseCount('chat_conversations', 1);
            assertDatabaseCount('chat_participants', 2);
        });
    });

    it('persists one message for concurrent retries', function (): void {
        withChatConcurrencyDatabase(function (): void {
            [$sender, $recipient] = createChatUsers();
            $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);
            $operation = ['action' => 'send', 'actor' => $sender->id, 'conversation' => $conversation->id, 'body' => 'Concurrent retry', 'token' => (string) Str::uuid()];

            $results = runConcurrentChatOperations([$operation, $operation]);

            expect($results[0])->toBe($results[1])
                ->and($conversation->fresh()->last_message_sequence)->toBe(1);
            assertDatabaseCount('chat_messages', 1);
        });
    });

    it('allocates distinct contiguous sequences for concurrent sends from both participants', function (): void {
        withChatConcurrencyDatabase(function (): void {
            [$sender, $recipient] = createChatUsers();
            $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);

            $results = runConcurrentChatOperations([
                ['action' => 'send', 'actor' => $sender->id, 'conversation' => $conversation->id, 'body' => 'First writer', 'token' => (string) Str::uuid()],
                ['action' => 'send', 'actor' => $recipient->id, 'conversation' => $conversation->id, 'body' => 'Second writer', 'token' => (string) Str::uuid()],
            ]);

            expect($results[0])->not->toBe($results[1])
                ->and(ChatMessage::query()->orderBy('sequence')->pluck('sequence')->all())->toBe([1, 2])
                ->and($conversation->fresh()->last_message_sequence)->toBe(2);
        });
    });

    it('keeps the highest cursor when read updates race', function (): void {
        withChatConcurrencyDatabase(function (): void {
            [$sender, $recipient] = createChatUsers();
            $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);
            $send = resolve(SendChatMessage::class);
            $send->handle($sender, $conversation, ['body' => 'First', 'client_message_id' => (string) Str::uuid()]);
            $send->handle($sender, $conversation, ['body' => 'Second', 'client_message_id' => (string) Str::uuid()]);

            runConcurrentChatOperations([
                ['action' => 'read', 'actor' => $recipient->id, 'conversation' => $conversation->id, 'sequence' => 2],
                ['action' => 'read', 'actor' => $recipient->id, 'conversation' => $conversation->id, 'sequence' => 1],
            ]);

            assertDatabaseHas('chat_participants', ['conversation_id' => $conversation->id, 'user_id' => $recipient->id, 'last_read_sequence' => 2]);
        });
    });
});
