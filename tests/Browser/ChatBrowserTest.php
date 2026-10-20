<?php

declare(strict_types=1);

use App\Actions\Chat\MarkChatConversationRead;
use App\Actions\Chat\SendChatMessage;
use App\Actions\Chat\StartDirectConversation;
use App\Enums\AppLayout;
use App\Events\ChatMessageCreated;
use App\Events\ChatReadUpdated;
use App\Events\ChatTypingUpdated;
use App\Models\ChatMessage;
use App\Models\User;
use App\Settings\LayoutSettings;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;

describe('Chat browser', function (): void {
    it('starts conversations, preserves drafts, handles keyboard and IME, and persists messages', function (AppLayout $layout): void {
        [$actor, $recipient] = createChatUsers();
        $recipient->update(['name' => 'Chat Recipient']);
        [$other] = createChatUsers();
        $other->update(['name' => 'Other Recipient']);
        $settings = resolve(LayoutSettings::class);
        $settings->app_layout = $layout;
        $settings->save();
        actingAs($actor);
        $page = visit(route('chat.index'));
        $page->assertSee(__('chat.status.empty_inbox'))->click('button[aria-label="'.__('chat.button.new').'"]')
            ->fill('#chat-recipient-search', 'Chat Recipient')->click('button:has-text("Chat Recipient")')
            ->assertVisible('#chat-body')->fill('#chat-body', 'Draft for first');
        $page->click('button[aria-label="'.__('chat.button.new').'"]')->fill('#chat-recipient-search', 'Other Recipient')
            ->click('button:has-text("Other Recipient")')->assertValue('#chat-body', '')
            ->fill('#chat-body', 'Draft for other')->click('button[aria-pressed]:has-text("Chat Recipient")')
            ->assertValue('#chat-body', 'Draft for first');
        $page->script('() => { const input = document.querySelector("#chat-body"); input.dispatchEvent(new CompositionEvent("compositionstart", { bubbles: true })); input.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter", isComposing: true, bubbles: true })); }');
        $page->assertValue('#chat-body', 'Draft for first');
        assertDatabaseCount('chat_messages', 0);
        $page->script('() => document.querySelector("#chat-body").dispatchEvent(new CompositionEvent("compositionend", { bubbles: true }))');
        $page->fill('#chat-body', 'First line')->keys('#chat-body', 'Shift+Enter')->assertValue('#chat-body', "First line\n")->fill('#chat-body', "First line\nSecond line")->keys('#chat-body', 'Enter')
            ->assertSee(__('chat.status.saved'))->assertValue('#chat-body', '')->assertNoJavaScriptErrors();
        expect(ChatMessage::query()->sole()->body)->toBe("First line\nSecond line");
        $page->navigate(route('chat.index'))->click('button[aria-pressed]:has-text("Chat Recipient")')->assertSee('Second line')
            ->resize(390, 844)->assertVisible('#chat-body')->assertScript('() => document.documentElement.scrollWidth <= innerWidth')
            ->screenshot(false, 'chat-mobile')->click('button[aria-label="'.__('chat.button.back').'"]')->assertSee('Other Recipient')
            ->assertNoJavaScriptErrors();
    })->with([AppLayout::Sidebar, AppLayout::Header]);

    it('retries a lost response with the original token without storing a second message', function (): void {
        [$actor, $recipient] = createChatUsers();
        $recipient->update(['name' => 'Retry Recipient']);
        resolve(StartDirectConversation::class)->handle($actor, $recipient);
        actingAs($actor);
        $page = visit(route('chat.index'))->click('button[aria-pressed]:has-text("Retry Recipient")')->assertVisible('#chat-body');
        $page->script(<<<'JS_WRAP'
            () => {
                const open = XMLHttpRequest.prototype.open;
                const send = XMLHttpRequest.prototype.send;
                window.chatTokens = [];
                window.loseChatResponse = true;
                XMLHttpRequest.prototype.open = function(method, url, ...args) {
                    this.chatSend = method.toUpperCase() === 'POST' && String(url).includes('/messages');
                    return open.call(this, method, url, ...args);
                };
                XMLHttpRequest.prototype.send = function(data) {
                    if (this.chatSend) {
                        window.chatTokens.push(JSON.parse(data).client_message_id);
                        if (window.loseChatResponse) {
                            window.loseChatResponse = false;
                            this.addEventListener('readystatechange', () => { if (this.readyState === 2) this.abort(); });
                        }
                    }
                    return send.call(this, data);
                };
            }
        JS_WRAP);
        $page->fill('#chat-body', 'Retry once')->keys('#chat-body', 'Enter')->assertSee(__('chat.error.send'));
        assertDatabaseCount('chat_messages', 1);
        $page->click(__('chat.button.retry'))->assertSee(__('chat.status.saved'))->assertDontSee(__('chat.error.send'))
            ->assertScript('() => window.chatTokens.length === 2 && window.chatTokens[0] === window.chatTokens[1]')
            ->assertScript('() => document.querySelectorAll("[data-test=chat-message-body]").length === 1')
            ->assertNoJavaScriptErrors();
        assertDatabaseCount('chat_messages', 1);
    });

    it('anchors older history, escapes long content and refreshes incoming messages through HTTP', function (): void {
        [$actor, $recipient] = createChatUsers();
        $actor->update(['timezone' => 'Asia/Jakarta']);
        $recipient->update(['name' => 'History Recipient']);
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        foreach (range(1, 65) as $sequence) {
            ChatMessage::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $recipient->id, 'sequence' => $sequence, 'body' => 'History message '.$sequence, 'created_at' => '2026-10-20 03:00:00']);
        }
        $conversation->update(['last_message_sequence' => 65]);
        actingAs($actor);
        $page = visit(route('chat.index'))->click('button[aria-pressed]:has-text("History Recipient")')->assertSee('History message 65');
        $page->wait(0.5);
        expect($conversation->participants()->where('user_id', $actor->id)->firstOrFail()->last_read_sequence)->toBe(65);
        $page->script('() => { const viewport = document.querySelector("[data-slot=message-scroller-viewport]"); viewport.dispatchEvent(new WheelEvent("wheel", { bubbles: true })); viewport.scrollTop = 160; viewport.dispatchEvent(new Event("scroll")); const items = [...viewport.querySelectorAll("[data-message-id]")]; const item = items.find(item => item.getBoundingClientRect().top >= viewport.getBoundingClientRect().top); window.chatAnchor = item.dataset.messageId; window.chatAnchorTop = item.getBoundingClientRect().top - viewport.getBoundingClientRect().top; }');
        $page->click(__('chat.button.older'))->assertSee('History message 1');
        $page->assertScript('() => Math.abs(document.querySelector(`[data-message-id="${window.chatAnchor}"]`).getBoundingClientRect().top - document.querySelector("[data-slot=message-scroller-viewport]").getBoundingClientRect().top - window.chatAnchorTop) < 5');
        $page->assertScript('() => document.querySelector("[data-slot=message-scroller-viewport]").scrollTop > 0');
        $page->assertSee('10:00');
        $body = '<img src=x onerror="window.chatXss=true">'.str_repeat('LongWord', 300);
        resolve(SendChatMessage::class)->handle($recipient, $conversation, ['body' => $body, 'client_message_id' => (string) Str::uuid()]);
        $page->click('button[aria-label="'.__('chat.button.refresh_history').'"]')->assertSee($body)
            ->assertScript('() => !window.chatXss && document.querySelectorAll("[data-slot=bubble-content] img").length === 0')
            ->assertScript('() => document.documentElement.scrollWidth <= innerWidth')
            ->screenshot(false, 'chat-desktop')->resize(390, 844)->assertScript('() => document.documentElement.scrollWidth <= innerWidth')
            ->assertNoJavaScriptErrors();
        foreach (range(67, 126) as $sequence) {
            ChatMessage::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $recipient->id, 'sequence' => $sequence, 'body' => 'Catch-up message '.$sequence]);
        }
        $conversation->update(['last_message_sequence' => 126]);
        $page->fill('#chat-body', 'Own message ahead of catch-up')->keys('#chat-body', 'Enter')->assertSee(__('chat.status.saved'))
            ->assertValue('#chat-body', '');
        $page->click('button[aria-label="'.__('chat.button.refresh_history').'"]')->assertSee('Catch-up message 126')
            ->assertScript('() => document.querySelectorAll("[data-test=chat-message-body]").length === 127')
            ->assertNoJavaScriptErrors();
        $page->script('() => { const viewport = document.querySelector("[data-slot=message-scroller-viewport]"); viewport.scrollTop = viewport.scrollHeight; viewport.dispatchEvent(new Event("scroll")); }');
        $page->wait(0.5)->assertMissing('button[aria-pressed] [data-slot=badge]');
        expect($conversation->participants()->where('user_id', $actor->id)->firstOrFail()->last_read_sequence)->toBe(127);
    });

    it('supports Indonesian copy, dark mode and message length validation on small screens', function (): void {
        [$actor, $recipient] = createChatUsers();
        $recipient->update(['name' => 'Penerima Chat']);
        resolve(StartDirectConversation::class)->handle($actor, $recipient);
        app()->setLocale('id');
        actingAs($actor);
        $page = visit(route('chat.index'))->inDarkMode()->resize(390, 844)
            ->click('button[aria-pressed]:has-text("Penerima Chat")')->assertVisible('#chat-body')
            ->assertSee(__('chat.helper.keyboard'))->fill('#chat-body', str_repeat('界', 4001))
            ->assertSee(__('chat.error.length'))->assertAttribute('#chat-body', 'aria-invalid', 'true');
        assertDatabaseCount('chat_messages', 0);
        $page->fill('#chat-body', 'Pesan singkat')->keys('#chat-body', 'Enter')->assertSee(__('chat.status.saved'))
            ->assertScript('() => document.querySelector("#chat-body").getBoundingClientRect().bottom < innerHeight')
            ->assertScript('() => document.documentElement.scrollWidth <= innerWidth')
            ->screenshot(false, 'chat-mobile-dark-id')->assertNoJavaScriptErrors();
    });

    it('hides chat navigation without permission', function (): void {
        actingAs(User::factory()->create());
        visit(route('dashboard'))->assertMissing('a[href="'.route('chat.index', absolute: false).'"]')->assertNoJavaScriptErrors();
    });

    it('shows the unread navigation badge without creating database notifications', function (): void {
        [$actor, $recipient] = createChatUsers();
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        foreach (['First unread', 'Second unread'] as $body) {
            resolve(SendChatMessage::class)->handle($recipient, $conversation, ['body' => $body, 'client_message_id' => (string) Str::uuid()]);
        }
        actingAs($actor);
        $link = 'a[href="'.route('chat.index', absolute: false).'"]';

        $page = visit(route('dashboard'))->assertVisible($link.' [data-slot=badge]')
            ->assertScript('() => document.querySelector('.json_encode($link).')?.querySelector("[data-slot=badge]")?.textContent.trim() === "2"');
        assertDatabaseCount('notifications', 0);

        resolve(MarkChatConversationRead::class)->handle($actor, $conversation, 2);

        $page->navigate(route('dashboard'))->assertMissing($link.' [data-slot=badge]')->assertNoJavaScriptErrors();
    });

    it('marks messages read only while the document is focused', function (): void {
        [$actor, $recipient] = createChatUsers();
        $recipient->update(['name' => 'Focus Recipient']);
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        foreach (['Focus message one', 'Focus message two'] as $body) {
            resolve(SendChatMessage::class)->handle($recipient, $conversation, ['body' => $body, 'client_message_id' => (string) Str::uuid()]);
        }
        $participant = $conversation->participants()->where('user_id', $actor->id)->firstOrFail();
        actingAs($actor);
        $page = visit(route('chat.index'));
        $page->script('() => { document.hasFocus = () => false; }');
        $page->click('button[aria-pressed]:has-text("Focus Recipient")')
            ->assertSee('Focus message two')
            ->wait(0.5);
        expect($participant->refresh()->last_read_sequence)->toBe(0);

        $page->script('() => { document.hasFocus = () => true; window.dispatchEvent(new Event("focus")); }');
        $page->wait(0.5);
        expect($participant->refresh()->last_read_sequence)->toBe(2);
        $page->assertMissing('button[aria-pressed] [data-slot=badge]')->assertNoJavaScriptErrors();
    });

    it('shows typing state with expiry and cancels it on send, switch and disconnect', function (): void {
        config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => 'browser-key', 'broadcasting.connections.reverb.secret' => 'browser-secret', 'broadcasting.connections.reverb.app_id' => 'browser-app']);
        require base_path('routes/channels.php');
        [$actor, $recipient] = createChatUsers();
        $recipient->update(['name' => 'Typing Recipient']);
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        [$other] = createChatUsers();
        $other->update(['name' => 'Typing Other']);
        $otherConversation = resolve(StartDirectConversation::class)->handle($actor, $other);

        actingAs($actor);
        $page = visit(route('chat.index'));
        $page->page()->context()->addInitScript(chatRealtimeSocketScript());
        $page->navigate(route('chat.index'))
            ->assertAttribute('[data-test="chat-connection"]', 'data-state', 'connected')
            ->click('button[aria-pressed]:has-text("Typing Recipient")')
            ->assertVisible('#chat-body');

        $typingLabel = __('chat.status.typing', ['name' => 'Typing Recipient']);
        // Browser tests freeze PHP time, so expiry has to come from the browser clock.
        $deliverTyping = static fn (string $conversationId, string $userId, bool $typing, string $expiresAt): string => '() => window.chatSocket.deliver('.json_encode(ChatTypingUpdated::class).', { ...'.json_encode(new ChatTypingUpdated($conversationId, $userId, $typing, now()->toISOString(), [$actor->id, $recipient->id])->broadcastWith()).', expires_at: '.$expiresAt.' })';

        $page->script($deliverTyping($conversation->id, (string) $recipient->id, true, 'new Date(Date.now() + 4000).toISOString()'));
        $page->assertSee($typingLabel);
        $page->script($deliverTyping($conversation->id, (string) $recipient->id, false, 'new Date(Date.now() + 4000).toISOString()'));
        $page->assertDontSee($typingLabel);
        $page->script($deliverTyping($conversation->id, (string) $recipient->id, true, 'new Date(Date.now() - 1000).toISOString()'));
        $page->assertDontSee($typingLabel);

        $page->script(<<<'JS_WRAP'
            () => {
                const open = XMLHttpRequest.prototype.open;
                const send = XMLHttpRequest.prototype.send;
                window.chatTypingRequests = [];
                XMLHttpRequest.prototype.open = function(method, url, ...args) {
                    this.chatUrl = String(url);
                    return open.call(this, method, url, ...args);
                };
                XMLHttpRequest.prototype.send = function(data) {
                    if (this.chatUrl.includes('/typing')) {
                        window.chatTypingRequests.push({url: this.chatUrl, typing: JSON.parse(data).typing});
                    }
                    return send.call(this, data);
                };
            }
        JS_WRAP);
        $page->fill('#chat-body', 'Typing in progress')->wait(0.4)
            ->assertScript('() => window.chatTypingRequests.some(request => request.typing === true)')
            ->keys('#chat-body', 'Enter')->assertSee(__('chat.status.saved'))->wait(0.5)
            ->assertScript('() => window.chatTypingRequests.some(request => request.typing === false)');

        $page->click('button[aria-pressed]:has-text("Typing Other")')
            ->fill('#chat-body', 'Another draft')->wait(0.4)
            ->assertScript('() => window.chatTypingRequests.some(request => request.typing === true && request.url.includes('.json_encode($otherConversation->id).'))')
            ->click('button[aria-pressed]:has-text("Typing Recipient")')->wait(0.5)
            ->assertScript('() => window.chatTypingRequests.some(request => request.typing === false && request.url.includes('.json_encode($otherConversation->id).'))');

        $page->script($deliverTyping($conversation->id, (string) $recipient->id, true, 'new Date(Date.now() + 4000).toISOString()'));
        $page->assertSee($typingLabel);
        $page->script('() => window.chatSocket.close()');
        $page->assertDontSee($typingLabel)->assertNoJavaScriptErrors();
    });

    it('merges realtime messages, reconciles reconnect catch-up and syncs read state without reload', function (): void {
        config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => 'browser-key', 'broadcasting.connections.reverb.secret' => 'browser-secret', 'broadcasting.connections.reverb.app_id' => 'browser-app']);
        require base_path('routes/channels.php');
        [$actor, $recipient] = createChatUsers();
        $recipient->update(['name' => 'Realtime Recipient']);
        $conversation = resolve(StartDirectConversation::class)->handle($actor, $recipient);
        [$other] = createChatUsers();
        $other->update(['name' => 'Realtime Other']);
        $otherConversation = resolve(StartDirectConversation::class)->handle($actor, $other);

        actingAs($actor);
        $page = visit(route('chat.index'));
        $page->page()->context()->addInitScript(chatRealtimeSocketScript());
        $page->navigate(route('chat.index'));
        $page->assertScript('() => Boolean(window.chatSocket?.channel)')
            ->assertAttribute('[data-test="chat-connection"]', 'data-state', 'connected')
            ->click('button[aria-pressed]:has-text("Realtime Recipient")')
            ->assertVisible('#chat-body');

        $message = resolve(SendChatMessage::class)->handle($recipient, $conversation, ['body' => 'Realtime incoming', 'client_message_id' => (string) Str::uuid()]);
        $delivery = '() => window.chatSocket.deliver('.json_encode(ChatMessageCreated::class).', '.json_encode(new ChatMessageCreated($message, [$actor->id, $recipient->id])->broadcastWith()).')';
        $page->script($delivery);
        $page->assertSee('Realtime incoming')
            ->assertScript('() => document.querySelectorAll("[data-test=chat-message-body]").length === 1');
        $page->script($delivery);
        $page->assertScript('() => document.querySelectorAll("[data-test=chat-message-body]").length === 1');

        $otherMessage = resolve(SendChatMessage::class)->handle($other, $otherConversation, ['body' => 'Other incoming', 'client_message_id' => (string) Str::uuid()]);
        $page->script('() => window.chatSocket.deliver('.json_encode(ChatMessageCreated::class).', '.json_encode(new ChatMessageCreated($otherMessage, [$actor->id, $other->id])->broadcastWith()).')');
        $page->assertScript('() => document.querySelector("button[aria-pressed]").textContent.includes("Realtime Other")')
            ->assertScript('() => document.querySelector("button[aria-pressed]").textContent.includes("Other incoming")');

        $second = resolve(SendChatMessage::class)->handle($actor, $conversation, ['body' => 'Second message', 'client_message_id' => (string) Str::uuid()]);
        $third = resolve(SendChatMessage::class)->handle($recipient, $conversation, ['body' => 'Third message', 'client_message_id' => (string) Str::uuid()]);
        $page->script('() => { window.chatSocket.deliver('.json_encode(ChatMessageCreated::class).', '.json_encode(new ChatMessageCreated($third, [$actor->id, $recipient->id])->broadcastWith()).'); window.chatSocket.deliver('.json_encode(ChatMessageCreated::class).', '.json_encode(new ChatMessageCreated($second, [$actor->id, $recipient->id])->broadcastWith()).'); }');
        $page->assertScript('() => [...document.querySelectorAll("[data-test=chat-message-body]")].map(node => node.textContent.trim()).join("|") === "Realtime incoming|Second message|Third message"');

        $missed = resolve(SendChatMessage::class)->handle($recipient, $conversation, ['body' => 'Missed while offline', 'client_message_id' => (string) Str::uuid()]);
        $page->script('() => window.chatSocket.deliver("pusher_internal:subscription_succeeded", {})');
        $page->assertSee('Missed while offline')
            ->assertScript('() => document.querySelectorAll("[data-test=chat-message-body]").length === 4');

        $page->assertVisible('button[aria-pressed]:has-text("Realtime Recipient") [data-slot=badge]');
        resolve(MarkChatConversationRead::class)->handle($recipient, $conversation, $missed->sequence);
        $page->script('() => window.chatSocket.deliver('.json_encode(ChatReadUpdated::class).', '.json_encode(new ChatReadUpdated($conversation->id, $recipient->id, $missed->sequence, [$actor->id, $recipient->id])->broadcastWith()).')');
        $page->assertSee(__('chat.status.read'));
        resolve(MarkChatConversationRead::class)->handle($actor, $conversation, $missed->sequence);
        $page->script('() => window.chatSocket.deliver('.json_encode(ChatReadUpdated::class).', '.json_encode(new ChatReadUpdated($conversation->id, $actor->id, $missed->sequence, [$actor->id, $recipient->id])->broadcastWith()).')');
        $page->assertMissing('button[aria-pressed]:has-text("Realtime Recipient") [data-slot=badge]')
            ->assertNoJavaScriptErrors();
    });
});
