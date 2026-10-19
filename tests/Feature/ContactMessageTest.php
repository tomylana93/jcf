<?php

declare(strict_types=1);

use App\Actions\Website\SetContactMessageHandled;
use App\Enums\Permission;
use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use App\Settings\GeneralSettings;
use App\Settings\WebsiteSettings;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withServerVariables;

/** @return array<string, string> */
function contactInput(): array
{
    return ['name' => 'Contact visitor', 'email' => 'visitor@example.com', 'subject' => 'Service enquiry', 'message' => 'Please provide further details.', 'website_url' => ''];
}

function enableContactWebsite(): void
{
    $settings = resolve(GeneralSettings::class);
    $settings->public_pages_enabled = true;
    $settings->save();
}

describe('Public contact and protected inbox', function (): void {
    it('hides the form and rejects submissions when public access is disabled', function (): void {
        $settings = resolve(GeneralSettings::class);
        $settings->public_pages_enabled = false;
        $settings->save();
        get('/contact')->assertNotFound();
        postJson('/contact', contactInput())->assertNotFound();
        expect(ContactMessage::query()->count())->toBe(0);
    });

    it('rejects a request without a CSRF token', function (): void {
        enableContactWebsite();
        app()->bind(PreventRequestForgery::class, fn () => new class(app(), resolve(Encrypter::class)) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        postJson('/contact', contactInput())->assertStatus(419);
        expect(ContactMessage::query()->count())->toBe(0);
    });

    it('renders contact details without private messages in every preset', function (string $preset): void {
        enableContactWebsite();
        $settings = resolve(WebsiteSettings::class);
        $settings->preset = $preset;
        $settings->contact = ['email' => 'office@example.com', 'phone' => '+6212345', 'address' => 'Configured address', 'hours' => 'Weekdays 09–17', 'location_url' => 'https://example.com/map', 'social_links' => [['label' => 'Social', 'href' => 'https://example.com/social']]];
        $settings->save();
        ContactMessage::factory()->create(['message' => 'Private inbox body']);
        get('/contact')->assertOk()->assertDontSee('Private inbox body')->assertInertia(fn (Assert $page): Assert => $page->component('public/Contact')->where('website.preset', $preset)->where('website.page.kind', 'contact')->where('website.contact.email', 'office@example.com')->where('website.contact.hours', 'Weekdays 09–17')->missing('messages')->missing('website.contact_notification_email'));
    })->with(['corporate', 'editorial', 'minimal']);

    it('stores only allowed fields and does not return sender data', function (): void {
        enableContactWebsite();
        Notification::fake();
        postJson('/contact', [...contactInput(), 'handled_at' => now()->toIso8601String(), 'user_id' => 'forged', 'id' => 'forged'])->assertCreated()->assertExactJson(['message' => __('contact.status.sent')])->assertHeader('Cache-Control', 'no-store, private');
        $record = ContactMessage::query()->sole();
        expect($record->name)->toBe('Contact visitor')->and($record->email)->toBe('visitor@example.com')->and($record->message)->toBe('Please provide further details.')->and($record->handled_at)->toBeNull()->and($record->id)->not->toBe('forged');
        Notification::assertNothingSent();
    });

    it('rejects invalid input and spam without saving', function (array $override, array $errors): void {
        enableContactWebsite();
        postJson('/contact', [...contactInput(), ...$override])->assertUnprocessable()->assertJsonValidationErrors($errors);
        expect(ContactMessage::query()->count())->toBe(0);
    })->with([
        [['name' => '', 'email' => '', 'subject' => '', 'message' => ''], ['name', 'email', 'subject', 'message']],
        [['email' => 'invalid'], ['email']],
        [['name' => str_repeat('a', 151), 'subject' => str_repeat('b', 201), 'message' => str_repeat('c', 5001)], ['name', 'subject', 'message']],
        [['website_url' => 'https://spam.example'], ['website_url']],
        [['message' => ['bad']], ['message']],
    ]);

    it('throttles repeated submissions and allows another IP', function (): void {
        enableContactWebsite();
        withServerVariables(['REMOTE_ADDR' => '192.0.2.41']);
        foreach (range(1, 5) as $attempt) {
            postJson('/contact', contactInput())->assertCreated();
        }
        postJson('/contact', contactInput())->assertTooManyRequests()->assertHeader('Retry-After');
        expect(ContactMessage::query()->count())->toBe(5);
        withServerVariables(['REMOTE_ADDR' => '192.0.2.42']);
        postJson('/contact', contactInput())->assertCreated();
    });

    it('sends an optional notification containing only an inbox link', function (): void {
        enableContactWebsite();
        config(['website.contact_notification_email' => 'inbox@example.com']);
        Notification::fake();
        postJson('/contact', contactInput())->assertCreated();
        $id = ContactMessage::query()->sole()->id;
        Notification::assertSentOnDemand(ContactMessageReceived::class, fn (ContactMessageReceived $notification, array $channels, object $notifiable): bool => $notification->messageId === $id && $notifiable->routes['mail'] === 'inbox@example.com');
        $mail = new ContactMessageReceived($id)->toMail(new stdClass);
        expect($mail->actionUrl)->toBe(route('website.inbox.show', ['message' => $id]))->and(implode(' ', $mail->introLines))->not->toContain('visitor@example.com', 'Please provide further details.');
    });

    it('retains accepted messages when email transport fails and logs no request data', function (): void {
        enableContactWebsite();
        config(['website.contact_notification_email' => 'inbox@example.com']);
        Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('Private transport detail'));
        Log::shouldReceive('warning')->once()->with('Contact inbox notification failed.', Mockery::on(fn (array $context): bool => array_keys($context) === ['contact_message_id']));
        postJson('/contact', contactInput())->assertCreated();
        expect(ContactMessage::query()->sole()->message)->toBe('Please provide further details.');
    });

    it('requires authentication and inbox permission for every operation', function (): void {
        $record = ContactMessage::factory()->create();
        get('/website/inbox')->assertRedirect(route('login', absolute: false));
        get('/website/inbox/'.$record->id)->assertRedirect(route('login', absolute: false));
        patch('/website/inbox/'.$record->id, ['handled' => true])->assertRedirect(route('login', absolute: false));
        actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::ConfigureWebsite);
        get('/website/inbox')->assertForbidden();
        get('/website/inbox/'.$record->id)->assertForbidden();
        patch('/website/inbox/'.$record->id, ['handled' => true])->assertForbidden();
        expect($record->fresh()->handled_at)->toBeNull();
    });

    it('lists minimal rows and lets inbox operators handle and reopen messages', function (): void {
        $actor = actingAsUserWithPermissions(Permission::ViewContactInbox);
        $record = ContactMessage::factory()->create(['message' => '<script>alert(1)</script>']);
        get('/website')->assertRedirect(route('website.inbox.index'));
        get('/website/inbox')->assertInertia(fn (Assert $page): Assert => $page->component('website/inbox/Index')->has('messages.data', 1)->where('messages.data.0.id', $record->id)->missing('messages.data.0.email')->missing('messages.data.0.name')->missing('messages.data.0.message'));
        get('/website/inbox/'.$record->id)->assertInertia(fn (Assert $page): Assert => $page->component('website/inbox/Show')->where('message.message', '<script>alert(1)</script>')->where('message.email', $record->email));
        patch('/website/inbox/'.$record->id, ['handled' => 'invalid'])->assertSessionHasErrors('handled');
        patch('/website/inbox/'.$record->id, ['handled' => true])->assertRedirect();
        $handled = $record->fresh()->handled_at;
        expect($handled)->not->toBeNull();
        resolve(SetContactMessageHandled::class)->handle($actor, $record->fresh(), true);
        expect($record->fresh()->handled_at->toIso8601String())->toBe($handled->toIso8601String());
        patch('/website/inbox/'.$record->id, ['handled' => false])->assertRedirect();
        expect($record->fresh()->handled_at)->toBeNull();
        get('/website/inbox/00000000-0000-0000-0000-000000000001')->assertNotFound();
    });

    it('rejects direct action calls without permission', function (): void {
        $record = ContactMessage::factory()->create();
        expect(fn () => resolve(SetContactMessageHandled::class)->handle(User::factory()->create(), $record, true))->toThrow(AuthorizationException::class)
            ->and($record->fresh()->handled_at)->toBeNull();
    });
});
