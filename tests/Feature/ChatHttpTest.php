<?php

declare(strict_types=1);

use App\Actions\Chat\SendChatMessage;
use App\Actions\Chat\StartDirectConversation;
use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Events\ChatTypingUpdated;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\TemporaryDirectory\TemporaryDirectory;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

describe('Chat HTTP', function (): void {
    it('excludes private chat requests and responses from the enabled devtools recorder', function (): void {
        [$actor, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        $directory = new TemporaryDirectory(storage_path('framework/testing/chat-devtools-'.Str::uuid()));
        $directory->create();
        config(['inertia.devtools.enabled' => true, 'inertia.devtools.storage.path' => $directory->path()]);
        actingAs($actor);

        try {
            get(route('dashboard'))->assertOk()->assertHeader('X-Inertia-Devtools-Id');
            get(route('chat.index'))->assertOk()->assertHeaderMissing('X-Inertia-Devtools-Id');
            postJson(route('chat.send', $conversation), ['body' => 'Private chat recorder probe', 'client_message_id' => (string) Str::uuid()])
                ->assertOk()->assertHeaderMissing('X-Inertia-Devtools-Id');
            getJson(route('chat.inbox'))->assertOk()->assertHeaderMissing('X-Inertia-Devtools-Id');
            getJson(route('chat.history', $conversation))->assertOk()->assertHeaderMissing('X-Inertia-Devtools-Id');
            foreach (glob($directory->path('*.json')) ?: [] as $file) {
                expect(file_get_contents($file))->not->toContain('Private chat recorder probe');
            }
        } finally {
            $directory->delete();
        }
    });

    it('requires authentication and chat permission for the page and discovery', function (): void {
        get(route('chat.index'))->assertRedirect(route('login'));
        getJson(route('chat.inbox'))->assertUnauthorized();
        actingAs(User::factory()->create());
        get(route('chat.index'))->assertForbidden();
        getJson(route('chat.inbox'))->assertForbidden();
        getJson(route('chat.recipients'))->assertForbidden();
        [$actor] = createChatUsers();
        actingAs($actor);
        get(route('chat.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('chat/Index'));
    });

    it('exposes only eligible recipients and minimal public identity fields', function (): void {
        [$actor, $eligible] = createChatUsers();
        $eligible->update(['name' => 'Eligible Person']);
        $direct = User::factory()->create(['name' => 'Eligible Direct']);
        $direct->givePermissionTo(Permission::UseChat);
        $admin = User::factory()->create(['name' => 'Eligible Admin']);
        $admin->assignRole(Role::SuperAdmin->value);
        User::factory()->create(['name' => 'Eligible NoPermission']);
        [$disabled] = createChatUsers();
        $disabled->forceFill(['name' => 'Eligible Disabled', 'status' => UserStatus::Disabled])->save();
        actingAs($actor);

        $response = getJson(route('chat.recipients', ['search' => 'Eligible']))->assertOk()->assertJsonCount(3, 'data');
        expect(array_keys($response->json('data.0')))->toBe(['id', 'name', 'avatar'])
            ->and(array_column($response->json('data'), 'id'))->toEqualCanonicalizing([$eligible->id, $direct->id, $admin->id]);
        getJson(route('chat.recipients', ['search' => '%']))->assertJsonCount(0, 'data');
        getJson(route('chat.recipients', ['search' => str_repeat('x', 101)]))->assertUnprocessable()->assertJsonValidationErrors('search');
    });

    it('paginates recipients without leaking accounts or repeating identities', function (): void {
        [$actor] = createChatUsers();
        $users = User::factory()->count(22)->create(['name' => 'Paginated Person']);
        foreach ($users as $user) {
            assignRolePermissions($user, Permission::UseChat);
        }
        actingAs($actor);
        $first = getJson(route('chat.recipients', ['search' => 'Paginated']))->assertJsonCount(20, 'data');
        $second = getJson(route('chat.recipients', ['search' => 'Paginated', 'cursor' => $first->json('next_cursor')]))->assertJsonCount(2, 'data');
        expect(array_intersect(array_column($first->json('data'), 'id'), array_column($second->json('data'), 'id')))->toBeEmpty();
    });

    it('starts idempotently, sends canonical messages and keeps data across HTTP reads', function (): void {
        [$actor, $recipient] = createChatUsers();
        actingAs($actor);
        $created = postJson(route('chat.store'), ['recipient_id' => $recipient->id])->assertOk();
        $id = $created->json('data.id');
        postJson(route('chat.store'), ['recipient_id' => $recipient->id])->assertJsonPath('data.id', $id);
        $token = (string) Str::uuid();
        $data = ['body' => '  <script>alert("escaped")</script>  ', 'client_message_id' => $token, 'sender_id' => $recipient->id];
        $sent = postJson(route('chat.send', $id), $data)->assertOk()->assertJsonPath('data.sequence', 1)->assertJsonPath('data.sender_id', $actor->id);
        postJson(route('chat.send', $id), $data)->assertJsonPath('data.id', $sent->json('data.id'));
        postJson(route('chat.send', $id), [...$data, 'body' => 'Changed'])->assertUnprocessable()->assertJsonValidationErrors('client_message_id');
        postJson(route('chat.send', $id), ['body' => ' '])->assertUnprocessable()->assertJsonValidationErrors(['body', 'client_message_id']);
        assertDatabaseCount('chat_conversations', 1);
        assertDatabaseCount('chat_messages', 1);
        assertDatabaseHas('chat_messages', ['id' => $sent->json('data.id'), 'sender_id' => $actor->id, 'body' => '<script>alert("escaped")</script>']);
        getJson(route('chat.history', $id))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $sent->json('data.id'));
        actingAs($recipient);
        getJson(route('chat.history', $id))->assertJsonPath('data.0.body', '<script>alert("escaped")</script>');
        getJson(route('chat.inbox'))->assertJsonPath('data.0.unread_count', 1)->assertJsonPath('data.0.last_message.sequence', 1)
            ->assertJsonPath('summary.unread_count', 1);
    });

    it('isolates inboxes and rejects foreign history, send and read requests', function (): void {
        [$actor, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        [$outsider] = createChatUsers();
        actingAs($outsider);

        getJson(route('chat.inbox'))->assertJsonCount(0, 'data');
        getJson(route('chat.history', $conversation))->assertForbidden();
        postJson(route('chat.send', $conversation), [])->assertForbidden();
        patchJson(route('chat.read', $conversation), ['sequence' => 1])->assertForbidden();
        getJson(route('chat.history', (string) Str::uuid()))->assertNotFound();
        getJson(route('chat.history', 'invalid'))->assertNotFound();
        assertDatabaseCount('chat_messages', 0);
    });

    it('paginates history by sequence in both directions without gaps or overlap', function (): void {
        [$actor, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        foreach (range(1, 105) as $sequence) {
            ChatMessage::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $actor->id, 'sequence' => $sequence]);
        }
        $conversation->update(['last_message_sequence' => 105]);
        actingAs($recipient);

        getJson(route('chat.history', $conversation))->assertJsonPath('data.0.sequence', 56)->assertJsonPath('data.49.sequence', 105)->assertJsonPath('has_more', true);
        getJson(route('chat.history', ['conversation' => $conversation, 'before' => 56]))->assertJsonPath('data.0.sequence', 6)->assertJsonPath('data.49.sequence', 55)->assertJsonPath('has_more', true);
        getJson(route('chat.history', ['conversation' => $conversation, 'before' => 6]))->assertJsonCount(5, 'data')->assertJsonPath('has_more', false);
        getJson(route('chat.history', ['conversation' => $conversation, 'after' => 0]))->assertJsonPath('data.0.sequence', 1)->assertJsonPath('data.49.sequence', 50)->assertJsonPath('has_more', true);
        getJson(route('chat.history', ['conversation' => $conversation, 'after' => 100]))->assertJsonCount(5, 'data')->assertJsonPath('data.0.sequence', 101)->assertJsonPath('has_more', false);
        getJson(route('chat.history', ['conversation' => $conversation, 'after' => 105]))->assertJsonCount(0, 'data');
        getJson(route('chat.history', ['conversation' => $conversation, 'before' => 1, 'after' => 1]))->assertUnprocessable()->assertJsonValidationErrors(['before', 'after']);
        getJson(route('chat.history', ['conversation' => $conversation, 'before' => -1]))->assertUnprocessable()->assertJsonValidationErrors('before');
    });

    it('counts only incoming unread messages and updates read cursors monotonically', function (): void {
        [$actor, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        $send = resolve(SendChatMessage::class);
        $send->handle($actor, $conversation, ['body' => 'Incoming one', 'client_message_id' => (string) Str::uuid()]);
        $send->handle($recipient, $conversation, ['body' => 'Own message', 'client_message_id' => (string) Str::uuid()]);
        $send->handle($actor, $conversation, ['body' => 'Incoming two', 'client_message_id' => (string) Str::uuid()]);
        actingAs($recipient);
        getJson(route('chat.inbox'))->assertJsonPath('data.0.unread_count', 2);
        patchJson(route('chat.read', $conversation), ['sequence' => 2])->assertJsonPath('last_read_sequence', 2);
        getJson(route('chat.inbox'))->assertJsonPath('data.0.unread_count', 1)->assertJsonPath('data.0.last_read_sequence', 2);
        patchJson(route('chat.read', $conversation), ['sequence' => 1])->assertJsonPath('last_read_sequence', 2);
        patchJson(route('chat.read', $conversation), ['sequence' => 99])->assertUnprocessable();
        assertDatabaseHas('chat_participants', ['conversation_id' => $conversation->id, 'user_id' => $recipient->id, 'last_read_sequence' => 2]);
    });

    it('shares an unread summary that excludes own messages and users without chat access', function (): void {
        [$actor, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        $send = resolve(SendChatMessage::class);
        $send->handle($actor, $conversation, ['body' => 'Incoming one', 'client_message_id' => (string) Str::uuid()]);
        $send->handle($recipient, $conversation, ['body' => 'Own reply', 'client_message_id' => (string) Str::uuid()]);
        $send->handle($actor, $conversation, ['body' => 'Incoming two', 'client_message_id' => (string) Str::uuid()]);

        actingAs($recipient);
        get(route('chat.index'))->assertInertia(fn (Assert $page): Assert => $page->where('chatSummary.unread_count', 2));
        get(route('dashboard'))->assertInertia(fn (Assert $page): Assert => $page->where('chatSummary.unread_count', 2));
        patchJson(route('chat.read', $conversation), ['sequence' => 3])->assertOk();
        get(route('chat.index'))->assertInertia(fn (Assert $page): Assert => $page->where('chatSummary.unread_count', 0));

        actingAs(User::factory()->create());
        get(route('dashboard'))->assertInertia(fn (Assert $page): Assert => $page->where('chatSummary.unread_count', 0));
    });

    it('exposes recipient read receipts in the inbox', function (): void {
        [$actor, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        $send = resolve(SendChatMessage::class);
        $send->handle($actor, $conversation, ['body' => 'First', 'client_message_id' => (string) Str::uuid()]);
        $send->handle($actor, $conversation, ['body' => 'Second', 'client_message_id' => (string) Str::uuid()]);

        actingAs($recipient);
        patchJson(route('chat.read', $conversation), ['sequence' => 2])->assertOk();

        actingAs($actor);
        getJson(route('chat.inbox'))
            ->assertJsonPath('data.0.recipient_last_read_sequence', 2)
            ->assertJsonPath('data.0.last_read_sequence', 0)
            ->assertJsonPath('data.0.unread_count', 0);
    });

    it('broadcasts typing only for participants and returns the accepted state', function (): void {
        [$actor, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        [$outsider] = createChatUsers();
        Event::fake([ChatTypingUpdated::class]);
        actingAs($actor);

        postJson(route('chat.typing', $conversation), ['typing' => true])->assertOk()->assertJsonPath('typing', true);
        postJson(route('chat.typing', $conversation), ['typing' => false])->assertOk()->assertJsonPath('typing', false);
        postJson(route('chat.typing', $conversation), [])->assertUnprocessable()->assertJsonValidationErrors('typing');

        Event::assertDispatchedTimes(ChatTypingUpdated::class, 2);
        Event::assertDispatched(ChatTypingUpdated::class, function (ChatTypingUpdated $event) use ($actor, $recipient, $conversation): bool {
            if (! $event->typing) {
                return false;
            }

            expect($event->conversationId)->toBe($conversation->id)
                ->and($event->userId)->toBe($actor->id)
                ->and($event->typing)->toBeTrue()
                ->and($event->expiresAt)->toBe(now()->addSeconds(4)->toISOString())
                ->and($event->userIds)->toBe([$recipient->id]);

            return true;
        });

        actingAs($outsider);
        postJson(route('chat.typing', $conversation), ['typing' => true])->assertForbidden();
        Event::assertDispatchedTimes(ChatTypingUpdated::class, 2);
    });

    it('keeps history readable but disables sending when a participant loses access', function (): void {
        [$actor, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        $recipient->forceFill(['status' => UserStatus::Disabled])->save();
        actingAs($actor);
        getJson(route('chat.inbox'))->assertJsonPath('data.0.can_send', false)->assertJsonPath('data.0.last_message', null);
        getJson(route('chat.history', $conversation))->assertOk();
        postJson(route('chat.send', $conversation), ['body' => 'No', 'client_message_id' => (string) Str::uuid()])->assertForbidden();
        postJson(route('chat.store'), ['recipient_id' => $recipient->id])->assertForbidden();
        actingAs($recipient);
        getJson(route('chat.inbox'))->assertRedirect(route('login'));
    });

    it('paginates inboxes deterministically and does not cache private responses', function (): void {
        [$actor] = createChatUsers();
        $users = User::factory()->count(21)->create();
        foreach ($users as $user) {
            assignRolePermissions($user, Permission::UseChat);
            resolve(StartDirectConversation::class)->handle($actor, $user);
        }
        actingAs($actor);
        $first = getJson(route('chat.inbox'))->assertJsonCount(20, 'data')->assertHeader('Cache-Control', 'no-store, private');
        $second = getJson(route('chat.inbox', ['cursor' => $first->json('next_cursor')]))->assertJsonCount(1, 'data');
        expect(array_intersect(array_column($first->json('data'), 'id'), array_column($second->json('data'), 'id')))->toBeEmpty();
    });

    it('limits discovery and creation independently from message sends', function (): void {
        [$actor, $recipient] = createChatUsers();
        actingAs($actor);
        foreach (range(1, 10) as $attempt) {
            postJson(route('chat.store'), ['recipient_id' => $recipient->id])->assertOk();
        }
        postJson(route('chat.store'), ['recipient_id' => $recipient->id])->assertTooManyRequests();
        getJson(route('chat.recipients'))->assertOk();
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        postJson(route('chat.send', $conversation), ['body' => 'Allowed', 'client_message_id' => (string) Str::uuid()])->assertOk();
    });
});
