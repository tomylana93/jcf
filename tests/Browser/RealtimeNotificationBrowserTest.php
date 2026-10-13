<?php

declare(strict_types=1);

use App\Actions\Notification\MarkAllNotificationsRead;
use App\Actions\Notification\MarkNotificationRead;
use App\Models\User;
use App\Notifications\ApplicationNotification;

use function Pest\Laravel\actingAs;

describe('realtime notification browser lifecycle', function (): void {
    it('refreshes notifications, deduplicates delivery, resubscribes and disconnects on logout', function (): void {
        config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => 'browser-key', 'broadcasting.connections.reverb.secret' => 'browser-secret', 'broadcasting.connections.reverb.app_id' => 'browser-app']);
        require base_path('routes/channels.php');
        $user = User::factory()->create();
        actingAs($user);
        $page = visit(route('notifications.index'));
        $page->page()->context()->addInitScript(<<<'JS_WRAP'
            (() => {
                window.realtimeSockets = [];
                window.realtimeReloads = 0;
                const originalOpen = XMLHttpRequest.prototype.open;
                XMLHttpRequest.prototype.open = function(method, url, ...args) {
                    if (String(url).includes('/notifications')) window.realtimeReloads++;
                    return originalOpen.call(this, method, url, ...args);
                };
                class TestSocket extends EventTarget {
                    constructor(url) {
                        super(); this.url = url; this.readyState = 0;
                        window.realtimeSockets.push(this);
                        setTimeout(() => {
                            this.readyState = 1;
                            this.onopen?.(new Event('open'));
                            this.deliver('pusher:connection_established', {socket_id: '123.456', activity_timeout: 120});
                        }, 0);
                    }
                    deliver(event, data) {
                        this.onmessage?.({data: JSON.stringify({event, data: JSON.stringify(data), channel: this.channel})});
                    }
                    send(payload) {
                        const message = JSON.parse(payload);
                        if (message.event === 'pusher:subscribe') {
                            this.channel = message.data.channel; window.realtimeChannel = this;
                            this.deliver('pusher_internal:subscription_succeeded', {});
                        }
                    }
                    close() { this.readyState = 3; this.onclose?.({code: 1000, wasClean: true}); }
                }
                TestSocket.CONNECTING = 0; TestSocket.OPEN = 1; TestSocket.CLOSING = 2; TestSocket.CLOSED = 3;
                window.WebSocket = TestSocket;
            })();
        JS_WRAP);
        $page->navigate(route('notifications.index'));
        $page->assertScript('window.realtimeSockets.some(socket => socket.channel)');
        $user->notify(new ApplicationNotification('account_updated'));
        $id = $user->notifications()->sole()->id;
        $page->script('() => window.realtimeChannel.deliver("Illuminate\\\\Notifications\\\\Events\\\\BroadcastNotificationCreated", {id: "'.$id.'", kind: "account_updated"})');

        $page->assertSee(__('notification.message.account_updated'))
            ->assertAttribute('[data-test="notification-bell"]', 'aria-label', __('notification.label.open', ['count' => 1]));
        $reloads = $page->script('() => window.realtimeReloads');
        $page->script('() => window.realtimeChannel.deliver("Illuminate\\\\Notifications\\\\Events\\\\BroadcastNotificationCreated", {id: "'.$id.'", kind: "account_updated"})');
        expect($page->script('() => window.realtimeReloads'))->toBe($reloads);

        resolve(MarkNotificationRead::class)->handle($user, $id);
        $page->script('() => window.realtimeChannel.deliver("App\\\\Events\\\\NotificationRead", {})');
        $page->assertAttribute('[data-test="notification-bell"]', 'aria-label', __('notification.label.open', ['count' => 0]));

        $user->notify(new ApplicationNotification('permissions_updated'));
        $page->script('() => window.realtimeChannel.deliver("pusher_internal:subscription_succeeded", {})');
        $page->assertSee(__('notification.message.permissions_updated'))
            ->assertAttribute('[data-test="notification-bell"]', 'aria-label', __('notification.label.open', ['count' => 1]));
        resolve(MarkAllNotificationsRead::class)->handle($user);
        $page->script('() => window.realtimeChannel.deliver("App\\\\Events\\\\NotificationRead", {})');
        $page->assertAttribute('[data-test="notification-bell"]', 'aria-label', __('notification.label.open', ['count' => 0]))
            ->assertNoJavaScriptErrors();
        $page->script('() => { window.realtimeClosed = false; const socket = window.realtimeChannel; const close = socket.close.bind(socket); socket.close = () => { window.realtimeClosed = true; sessionStorage.setItem("realtimeClosed", "yes"); close(); }; }');
        $page->click('[data-test="sidebar-menu-button"]')->click('[data-test="logout-button"]');
        $page->assertUrlIs(route('login'))->assertNoJavaScriptErrors();
        expect($page->script('() => sessionStorage.getItem("realtimeClosed")'))->toBe('yes');
    });
});
