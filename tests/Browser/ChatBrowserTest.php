<?php

declare(strict_types=1);

use App\Actions\Chat\SendChatMessage;
use App\Actions\Chat\StartDirectConversation;
use App\Enums\AppLayout;
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
        $page->click(__('chat.button.read'))->assertMissing('button[aria-pressed] [data-slot=badge]');
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
});
