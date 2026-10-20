<?php

declare(strict_types=1);

use App\Actions\Chat\SendChatMessage;
use App\Actions\Chat\StartDirectConversation;
use App\Http\Requests\Chat\MarkChatConversationReadRequest;
use App\Http\Requests\Chat\SendChatMessageRequest;
use App\Http\Requests\Chat\StartDirectConversationRequest;
use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\postJson;

describe('Chat request boundaries', function (): void {
    beforeEach(function (): void {
        Route::middleware(['web', 'auth'])->group(function (): void {
            Route::post('/_test/chat/start', fn (StartDirectConversationRequest $request): array => $request->validated());
            Route::post('/_test/chat/{conversation}/send', fn (SendChatMessageRequest $request, ChatConversation $conversation): array => $request->validated());
            Route::post('/_test/chat/{conversation}/read', fn (MarkChatConversationReadRequest $request, ChatConversation $conversation): array => $request->validated());
        });
    });

    it('requires authentication before resolving chat requests', function (): void {
        postJson('/_test/chat/start', [])->assertUnauthorized();
    });

    it('validates recipient identity and permission before exposing a start request', function (): void {
        [$sender, $recipient] = createChatUsers();
        actingAs($sender);

        postJson('/_test/chat/start', ['recipient_id' => $recipient->id])
            ->assertOk()->assertExactJson(['recipient_id' => $recipient->id]);
        postJson('/_test/chat/start', [])->assertUnprocessable()->assertJsonValidationErrors('recipient_id');
        postJson('/_test/chat/start', ['recipient_id' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors('recipient_id');
        postJson('/_test/chat/start', ['recipient_id' => (string) Str::uuid()])->assertUnprocessable()->assertJsonValidationErrors('recipient_id');

        actingAs(User::factory()->create());
        postJson('/_test/chat/start', ['recipient_id' => $recipient->id])->assertForbidden();
        assertDatabaseCount('chat_conversations', 0);
    });

    it('normalizes text and returns only message input accepted by the request', function (): void {
        [$sender, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);
        $token = (string) Str::uuid();
        actingAs($sender);

        postJson("/_test/chat/{$conversation->id}/send", [
            'body' => '  Message  ', 'client_message_id' => $token, 'sender_id' => $recipient->id,
        ])->assertOk()->assertExactJson(['body' => 'Message', 'client_message_id' => $token]);
        postJson("/_test/chat/{$conversation->id}/send", ['body' => null, 'client_message_id' => $token])
            ->assertUnprocessable()->assertJsonValidationErrors('body');
        postJson("/_test/chat/{$conversation->id}/send", ['body' => '  ', 'client_message_id' => $token])
            ->assertUnprocessable()->assertJsonValidationErrors('body');
        assertDatabaseCount('chat_messages', 0);
    });

    it('rejects sending and read requests from nonmembers', function (): void {
        [$sender, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);
        [$outsider] = createChatUsers();
        actingAs($outsider);

        postJson("/_test/chat/{$conversation->id}/send", [])->assertForbidden();
        postJson("/_test/chat/{$conversation->id}/read", ['sequence' => 1])->assertForbidden();
        assertDatabaseCount('chat_messages', 0);
    });

    it('accepts only read cursors that exist in the route-bound conversation', function (): void {
        [$sender, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($sender, $recipient);
        resolve(SendChatMessage::class)->handle($sender, $conversation, ['body' => 'First', 'client_message_id' => (string) Str::uuid()]);
        actingAs($recipient);

        postJson("/_test/chat/{$conversation->id}/read", ['sequence' => 1])
            ->assertOk()->assertExactJson(['sequence' => 1]);
        postJson("/_test/chat/{$conversation->id}/read", ['sequence' => 2])->assertUnprocessable()->assertJsonValidationErrors('sequence');
        postJson("/_test/chat/{$conversation->id}/read", ['sequence' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors('sequence');
        postJson("/_test/chat/{$conversation->id}/read", [])->assertUnprocessable()->assertJsonValidationErrors('sequence');
    });

    it('rejects requests when no conversation model has been bound', function (): void {
        [$sender] = createChatUsers();
        actingAs($sender);
        $send = SendChatMessageRequest::create('/', 'POST');
        $read = MarkChatConversationReadRequest::create('/', 'POST');
        $send->setUserResolver(fn (): User => $sender);
        $read->setUserResolver(fn (): User => $sender);

        expect($send->authorize())->toBeFalse()
            ->and($read->authorize())->toBeFalse()
            ->and(fn () => $read->rules())->toThrow(HttpException::class);
    });
});
