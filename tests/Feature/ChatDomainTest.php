<?php

declare(strict_types=1);

use App\Actions\Chat\MarkChatConversationRead;
use App\Actions\Chat\SendChatMessage;
use App\Actions\Chat\StartDirectConversation;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

describe('Direct chat domain', function (): void {
    it('reuses one conversation in either direction with exactly two participants', function (): void {
        [$sender, $recipient] = createChatUsers();
        $action = resolve(StartDirectConversation::class);

        $conversation = $action->handle($sender, $recipient);
        $repeated = $action->handle($recipient, $sender);

        expect($repeated->id)->toBe($conversation->id)
            ->and($conversation->type)->toBe('direct')
            ->and($conversation->last_message_sequence)->toBe(0)
            ->and($conversation->participants()->pluck('user_id')->all())->toEqualCanonicalizing([$sender->id, $recipient->id])
            ->and($conversation->participants()->firstOrFail()->conversation->id)->toBe($conversation->id)
            ->and($conversation->participants()->firstOrFail()->user->id)->toBeIn([$sender->id, $recipient->id]);
        assertDatabaseCount('chat_conversations', 1);
        assertDatabaseCount('chat_participants', 2);
    });

    it('rejects self conversations without creating records', function (): void {
        [$sender] = createChatUsers();

        try {
            resolve(StartDirectConversation::class)->handle($sender, $sender);
            test()->fail('A self conversation was accepted.');
        } catch (ValidationException $exception) {
            expect($exception->errors())->toBe(['recipient_id' => [__('chat.error.self_conversation')]]);
        }

        assertDatabaseCount('chat_conversations', 0);
        assertDatabaseCount('chat_participants', 0);
    });

    it('requires both participants to be active and permitted using current database state', function (string $target, string $change): void {
        [$sender, $recipient] = createChatUsers();
        $user = $target === 'sender' ? $sender : $recipient;
        $user->load('roles.permissions', 'permissions');

        if ($change === 'disabled') {
            User::query()->whereKey($user->id)->update(['status' => UserStatus::Disabled]);
        } else {
            $user->roles()->detach();
        }

        expect(fn () => resolve(StartDirectConversation::class)->handle($sender, $recipient))->toThrow(AuthorizationException::class);
        assertDatabaseCount('chat_conversations', 0);
    })->with([
        'disabled sender' => ['sender', 'disabled'],
        'disabled recipient' => ['recipient', 'disabled'],
        'unprivileged sender' => ['sender', 'permission'],
        'unprivileged recipient' => ['recipient', 'permission'],
    ]);

    it('refuses deleted participants even when a stale model is passed', function (): void {
        [$sender, $recipient] = createChatUsers();
        $recipient->delete();

        expect(fn () => resolve(StartDirectConversation::class)->handle($sender, $recipient))
            ->toThrow(HttpException::class);
        assertDatabaseCount('chat_conversations', 0);
    });

    it('allocates message sequences and ignores forged identity and cursor attributes', function (): void {
        [$sender, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);
        $send = resolve(SendChatMessage::class);
        $token = (string) Str::uuid();
        $first = $send->handle($sender, $conversation, [
            'body' => "  <script>alert('example')</script>\nPesan 你好  ",
            'client_message_id' => mb_strtoupper($token),
            'sender_id' => $recipient->id,
            'sequence' => 900,
            'conversation_id' => (string) Str::uuid(),
        ]);
        $second = $send->handle($recipient, $conversation, ['body' => 'Reply', 'client_message_id' => (string) Str::uuid()]);

        expect($first->sequence)->toBe(1)
            ->and($second->sequence)->toBe(2)
            ->and($first->sender->id)->toBe($sender->id)
            ->and($first->conversation->id)->toBe($conversation->id)
            ->and($first->client_message_id)->toBe($token)
            ->and($first->body)->toBe("<script>alert('example')</script>\nPesan 你好")
            ->and($conversation->fresh()->last_message_sequence)->toBe(2);
        assertDatabaseCount('chat_messages', 2);
        assertDatabaseHas('chat_messages', ['id' => $first->id, 'sender_id' => $sender->id, 'sequence' => 1]);
    });

    it('returns the canonical message on retries without allocating another sequence', function (): void {
        [$sender, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);
        $data = ['body' => 'Original message', 'client_message_id' => (string) Str::uuid()];
        $send = resolve(SendChatMessage::class);
        $message = $send->handle($sender, $conversation, $data);
        $retry = $send->handle($sender, $conversation, $data);

        expect($retry->id)->toBe($message->id)
            ->and($conversation->fresh()->last_message_sequence)->toBe(1);
        assertDatabaseCount('chat_messages', 1);
    });

    it('rejects reuse of a token for different content or conversation', function (bool $differentConversation): void {
        [$sender, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);
        $data = ['body' => 'Original message', 'client_message_id' => (string) Str::uuid()];
        $send = resolve(SendChatMessage::class);
        $send->handle($sender, $conversation, $data);

        if ($differentConversation) {
            [$other] = createChatUsers();
            $conversation = resolve(StartDirectConversation::class)->handle($sender, $other);
        } else {
            $data['body'] = 'Changed content';
        }

        try {
            $send->handle($sender, $conversation, $data);
            test()->fail('A conflicting retry was accepted.');
        } catch (ValidationException $exception) {
            expect($exception->errors())->toBe(['client_message_id' => [__('chat.error.idempotency_conflict')]]);
        }

        assertDatabaseCount('chat_messages', 1);
        expect($conversation->fresh()->last_message_sequence)->toBe($differentConversation ? 0 : 1);
    })->with(['different body' => false, 'different conversation' => true]);

    it('scopes idempotency to the sender', function (): void {
        [$sender, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);
        $data = ['body' => 'Same token', 'client_message_id' => (string) Str::uuid()];
        $send = resolve(SendChatMessage::class);

        expect($send->handle($sender, $conversation, $data)->id)
            ->not->toBe($send->handle($recipient, $conversation, $data)->id);
        assertDatabaseCount('chat_messages', 2);
    });

    it('rejects invalid messages from direct backend callers without changing state', function (array $overrides, string $field): void {
        [$sender, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);
        $data = array_replace(['body' => 'Valid', 'client_message_id' => (string) Str::uuid()], $overrides);

        try {
            resolve(SendChatMessage::class)->handle($sender, $conversation, $data);
            test()->fail('Invalid message was accepted.');
        } catch (ValidationException $exception) {
            expect($exception->errors())->toHaveKey($field);
        }

        assertDatabaseCount('chat_messages', 0);
        expect($conversation->fresh()->last_message_sequence)->toBe(0);
    })->with([
        'missing text' => [['body' => null], 'body'],
        'blank text' => [['body' => " \n\t "], 'body'],
        'unicode whitespace' => [['body' => "\u{2003}"], 'body'],
        'non string' => [['body' => ['forged']], 'body'],
        'oversized text' => [['body' => str_repeat('文', 4001)], 'body'],
        'missing token' => [['client_message_id' => null], 'client_message_id'],
        'invalid token' => [['client_message_id' => 'not-a-uuid'], 'client_message_id'],
    ]);

    it('accepts a message at the multibyte character limit', function (): void {
        [$sender, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);
        $body = str_repeat('文', 4000);

        expect(resolve(SendChatMessage::class)->handle($sender, $conversation, [
            'body' => $body, 'client_message_id' => (string) Str::uuid(),
        ])->body)->toBe($body);
    });

    it('requires membership even for superadmin and ignores preloaded forged participants', function (): void {
        [$sender, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);
        $outsider = User::factory()->create();
        $outsider->assignRole(Role::SuperAdmin);
        $conversation->setRelation('participants', collect([new ChatParticipant(['user_id' => $outsider->id])]));

        expect($outsider->can('view', $conversation))->toBeFalse()
            ->and($outsider->can('sendMessage', $conversation))->toBeFalse()
            ->and($outsider->can('markRead', $conversation))->toBeFalse()
            ->and(fn () => resolve(SendChatMessage::class)->handle($outsider, $conversation, [
                'body' => 'Unauthorized', 'client_message_id' => (string) Str::uuid(),
            ]))->toThrow(AuthorizationException::class)
            ->and(fn () => resolve(MarkChatConversationRead::class)->handle($outsider, $conversation, 1))->toThrow(AuthorizationException::class);
        assertDatabaseCount('chat_messages', 0);
    });

    it('stops new sends after a participant is disabled or loses permission while retaining history access for the other participant', function (string $change): void {
        [$sender, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);

        if ($change === 'disabled') {
            $recipient->forceFill(['status' => UserStatus::Disabled])->save();
        } else {
            $recipient->roles()->detach();
        }

        expect($sender->can('view', $conversation))->toBeTrue()
            ->and($sender->can('sendMessage', $conversation))->toBeFalse()
            ->and(fn () => resolve(SendChatMessage::class)->handle($sender, $conversation, [
                'body' => 'Blocked', 'client_message_id' => (string) Str::uuid(),
            ]))->toThrow(AuthorizationException::class);
        assertDatabaseCount('chat_messages', 0);
    })->with(['disabled', 'permission']);

    it('refuses sends to incomplete direct conversations or unsupported types', function (bool $incomplete): void {
        [$sender, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);

        if ($incomplete) {
            $conversation->participants()->where('user_id', $recipient->id)->delete();
        } else {
            $conversation->update(['type' => 'group']);
        }

        expect(fn () => resolve(SendChatMessage::class)->handle($sender, $conversation, [
            'body' => 'Blocked', 'client_message_id' => (string) Str::uuid(),
        ]))->toThrow(AuthorizationException::class);
    })->with(['incomplete participants' => true, 'unsupported type' => false]);

    it('advances only the requesting participant read cursor and never moves it backward', function (): void {
        [$sender, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);
        $send = resolve(SendChatMessage::class);
        $send->handle($sender, $conversation, ['body' => 'First', 'client_message_id' => (string) Str::uuid()]);
        $send->handle($sender, $conversation, ['body' => 'Second', 'client_message_id' => (string) Str::uuid()]);
        $read = resolve(MarkChatConversationRead::class);

        expect($read->handle($recipient, $conversation, 2)->last_read_sequence)->toBe(2)
            ->and($read->handle($recipient, $conversation, 1)->last_read_sequence)->toBe(2)
            ->and($read->handle($recipient, $conversation, 2)->last_read_sequence)->toBe(2);
        assertDatabaseHas('chat_participants', ['conversation_id' => $conversation->id, 'user_id' => $sender->id, 'last_read_sequence' => 0]);
        assertDatabaseHas('chat_participants', ['conversation_id' => $conversation->id, 'user_id' => $recipient->id, 'last_read_sequence' => 2]);
    });

    it('rejects cursors without a matching message in the selected conversation', function (int $sequence): void {
        [$sender, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);
        ChatMessage::factory()->create(['sequence' => 5]);

        expect(fn () => resolve(MarkChatConversationRead::class)->handle($sender, $conversation, $sequence))
            ->toThrow(ValidationException::class);
        assertDatabaseHas('chat_participants', ['conversation_id' => $conversation->id, 'user_id' => $sender->id, 'last_read_sequence' => 0]);
    })->with(['zero' => 0, 'negative' => -1, 'foreign cursor' => 5]);

    it('rolls back messages and sequences with the surrounding transaction', function (): void {
        [$sender, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);

        try {
            DB::transaction(function () use ($sender, $conversation): void {
                resolve(SendChatMessage::class)->handle($sender, $conversation, ['body' => 'Rollback', 'client_message_id' => (string) Str::uuid()]);
                throw new RuntimeException('Cancel transaction');
            });
        } catch (RuntimeException) {
            assertDatabaseCount('chat_messages', 0);
            expect($conversation->fresh()->last_message_sequence)->toBe(0);
        }
    });

    it('prevents messages from claiming a sender outside the conversation', function (): void {
        [$sender, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);
        $outsider = User::factory()->create();

        expect(fn () => DB::transaction(fn () => ChatMessage::factory()->create([
            'conversation_id' => $conversation->id, 'sender_id' => $outsider->id,
        ])))->toThrow(QueryException::class);
        assertDatabaseCount('chat_messages', 0);
    });

    it('removes a conversation and its history together without deleting its users', function (): void {
        [$sender, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);
        resolve(SendChatMessage::class)->handle($sender, $conversation, [
            'body' => 'History', 'client_message_id' => (string) Str::uuid(),
        ]);

        $conversation->delete();

        assertDatabaseCount('chat_conversations', 0);
        assertDatabaseCount('chat_participants', 0);
        assertDatabaseCount('chat_messages', 0);
        assertDatabaseCount('users', 2);
    });

    it('enforces unique conversation pairs participants sequences and tokens at the database boundary', function (string $constraint): void {
        [$sender, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);
        $message = resolve(SendChatMessage::class)->handle($sender, $conversation, ['body' => 'Original', 'client_message_id' => (string) Str::uuid()]);

        expect(fn () => DB::transaction(fn () => match ($constraint) {
            'pair' => ChatConversation::factory()->create(['direct_pair_key' => $conversation->direct_pair_key]),
            'participant' => ChatParticipant::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $sender->id]),
            'sequence' => ChatMessage::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $recipient->id, 'sequence' => 1]),
            'token' => ChatMessage::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $sender->id, 'sequence' => 2, 'client_message_id' => $message->client_message_id]),
        }))->toThrow(QueryException::class);
        assertDatabaseCount('chat_conversations', 1);
        assertDatabaseCount('chat_participants', 2);
        assertDatabaseCount('chat_messages', 1);
    })->with(['pair', 'participant', 'sequence', 'token']);
});
