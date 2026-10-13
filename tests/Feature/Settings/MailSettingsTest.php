<?php

declare(strict_types=1);

use App\Actions\Settings\GetMailPreview;
use App\Enums\BrandColor;
use App\Enums\LogoType;
use App\Enums\Permission;
use App\Models\User;
use App\Settings\BrandSettings;
use App\Settings\MailSettings;
use App\Settings\MailSettingsTestMessage;
use App\Settings\SettingsMailTransport;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

function mailSettingsPayload(array $overrides = []): array
{
    return array_replace([
        'mailer' => 'smtp',
        'smtp_host' => 'smtp.example.test',
        'smtp_port' => 587,
        'smtp_scheme' => 'smtp',
        'smtp_username' => 'mailer',
        'smtp_password' => 'app-password',
        'from_address' => 'hello@example.test',
        'from_name' => 'JCF',
        'show_logo' => true,
        'footer_text' => 'Questions? Contact support.',
    ], $overrides);
}

describe('Mail settings', function (): void {
    it('restricts settings and preview to authenticated users with permissions', function (): void {
        get(route('settings.mail.edit'))->assertRedirect(route('login'));
        get(route('settings.mail.preview'))->assertRedirect(route('login'));
        patch(route('settings.mail.update'), mailSettingsPayload())->assertRedirect(route('login'));
        post(route('settings.mail.test'), ['test_email' => 'recipient@example.test'])->assertRedirect(route('login'));

        $user = User::factory()->create();

        actingAs($user)->get(route('settings.mail.edit'))->assertForbidden();

        get(route('settings.mail.preview'))->assertForbidden();
        patch(route('settings.mail.update'), mailSettingsPayload())->assertForbidden();
        post(route('settings.mail.test'), ['test_email' => 'recipient@example.test'])->assertForbidden();
    });

    it('shows safe settings without exposing the SMTP password', function (): void {

        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ViewSettings, Permission::ManageSettings, Permission::ViewUsers, Permission::ManageUsers);

        actingAs($admin)->patch(route('settings.mail.update'), mailSettingsPayload())->assertSessionHasNoErrors();

        get(route('settings.mail.edit'))
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('settings/Mail')
                ->where('settings.mailer', 'smtp')
                ->where('settings.has_smtp_password', true)
                ->missing('settings.smtp_password'))
            ->assertDontSee('app-password');

        expect(resolve(MailSettings::class)->refresh()->smtp_password)->toBe('app-password')
            ->and((string) DB::table('settings')->where('group', 'mail')->where('name', 'smtp_password')->value('payload'))->not->toContain('app-password');
    });

    it('keeps the saved password when the password field is blank', function (): void {

        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ViewSettings, Permission::ManageSettings, Permission::ViewUsers, Permission::ManageUsers);

        actingAs($admin)->patch(route('settings.mail.update'), mailSettingsPayload())->assertSessionHasNoErrors();

        patch(route('settings.mail.update'), mailSettingsPayload([
            'smtp_password' => '',
            'smtp_host' => 'mail.example.test',

        ]))->assertSessionHasNoErrors();

        $settings = resolve(MailSettings::class)->refresh();

        expect($settings->smtp_password)->toBe('app-password')
            ->and($settings->smtp_host)->toBe('mail.example.test');
    });

    it('supports log mode and renders a preview without sending', function (): void {
        config()->set('mail.default', 'settings');

        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ViewSettings, Permission::ManageSettings, Permission::ViewUsers, Permission::ManageUsers);

        actingAs($admin)->patch(route('settings.mail.update'), mailSettingsPayload([
            'mailer' => 'log',
            'smtp_host' => '',
            'smtp_password' => '',
            'show_logo' => false,
            'footer_text' => '<script>unsafe</script>',
        ]))->assertSessionHasNoErrors();

        expect(Mail::mailer()->getSymfonyTransport())->toBeInstanceOf(SettingsMailTransport::class)
            ->and((string) Mail::mailer()->getSymfonyTransport())->toBe('settings-mail');

        get(route('settings.mail.preview'))->assertOk()->assertSeeHtml('&lt;script&gt;unsafe&lt;/script&gt;')->assertDontSeeHtml('<script>unsafe</script>');

        Mail::mailer()->getSymfonyTransport()->send(
            (new Email)->to('recipient@example.test')->subject('Test')->text('Body'),
        );
    });

    it('localizes notification mail wording', function (string $locale): void {
        app()->setLocale($locale);

        $html = (string) resolve(GetMailPreview::class)->handle()->render();

        expect($html)->toContain(__('notification.mail.greeting'))
            ->toContain(__('notification.mail.salutation'))
            ->toContain(__('notification.mail.action_fallback', ['actionText' => __('settings.mail.preview.action')]))
            ->not->toContain('notification.mail.');
    })->with(['en', 'id']);

    it('sends a test message through the selected mailer', function (): void {

        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ViewSettings, Permission::ManageSettings, Permission::ViewUsers, Permission::ManageUsers);

        expect((new MailSettingsTestMessage)->render())->toContain(__('settings.mail.test.heading'));

        Mail::fake();

        actingAs($admin)->post(route('settings.mail.test'), ['test_email' => 'recipient@example.test'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('settings.mail.edit'));

        Mail::assertSent(MailSettingsTestMessage::class, 'recipient@example.test');
    });

    it('validates the test recipient and reports delivery failures safely', function (): void {

        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ViewSettings, Permission::ManageSettings, Permission::ViewUsers, Permission::ManageUsers);

        actingAs($admin)->post(route('settings.mail.test'), ['test_email' => 'invalid'])
            ->assertInvalid(['test_email']);

        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('Internal transport failure'));

        post(route('settings.mail.test'), ['test_email' => 'recipient@example.test'])
            ->assertInvalid(['test_email' => __('settings.mail.status.test_failed')]);
    });

    it('rejects unsupported mailers and incomplete SMTP settings', function (array $data, string $field): void {

        $admin = User::factory()->create();
        assignRolePermissions($admin, Permission::ViewSettings, Permission::ManageSettings, Permission::ViewUsers, Permission::ManageUsers);

        actingAs($admin)->patch(route('settings.mail.update'), $data)->assertInvalid([$field]);

        expect(resolve(MailSettings::class)->refresh()->mailer)->toBe('environment');
    })->with([
        'unsupported mailer' => [mailSettingsPayload(['mailer' => 'sendmail']), 'mailer'],
        'missing host' => [mailSettingsPayload(['smtp_host' => '']), 'smtp_host'],
        'missing password' => [mailSettingsPayload(['smtp_password' => '']), 'smtp_password'],
        'invalid port' => [mailSettingsPayload(['smtp_port' => 70000]), 'smtp_port'],
        'invalid sender' => [mailSettingsPayload(['from_address' => 'invalid']), 'from_address'],
    ]);

    it('uses current SMTP settings each time a message is sent', function (): void {
        $settings = resolve(MailSettings::class);
        $settings->mailer = 'smtp';
        $settings->smtp_host = 'smtp.example.test';
        $settings->smtp_port = 587;
        $settings->smtp_scheme = 'smtp';
        $settings->smtp_username = 'mailer';
        $settings->smtp_password = 'secret';
        $settings->from_address = 'sender@example.test';
        $settings->from_name = 'JCF';
        $settings->save();

        $email = (new Email)->to('recipient@example.test')->subject('Test')->text('Body');
        $transport = Mockery::mock(TransportInterface::class);
        $transport->shouldReceive('send')->once()->with($email, null)->andReturn(null);
        $manager = Mockery::mock(MailManager::class);
        $manager->shouldReceive('createSymfonyTransport')->once()->with(Mockery::on(fn (array $config): bool => $config['host'] === 'smtp.example.test'
            && $config['password'] === 'secret'
            && $config['require_tls'] === true
        ))->andReturn($transport);

        new SettingsMailTransport($manager)->send($email);

        expect($email->getFrom()[0]->getAddress())->toBe('sender@example.test');
    });
});

describe('Mailpit and secret boundaries', function (): void {
    it('accepts local SMTP without authentication and never flashes submitted credentials', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ViewSettings, Permission::ManageSettings);
        actingAs($actor)->patch(route('settings.mail.update'), mailSettingsPayload([
            'smtp_host' => 'lerd-mailpit', 'smtp_port' => 1025, 'smtp_scheme' => 'none',
            'smtp_username' => '', 'smtp_password' => '',
        ]))->assertSessionHasNoErrors();

        expect(resolve(MailSettings::class)->refresh()->smtp_scheme)->toBe('none');

        patch(route('settings.mail.update'), mailSettingsPayload(['from_address' => 'invalid', 'smtp_password' => 'private-value']))
            ->assertInvalid('from_address')->assertSessionMissing('_old_input.smtp_password');
    });
});

describe('Branded mail rendering', function (): void {
    it('uses the saved logo and matching accent in outgoing mail', function (string $color, string $accent): void {
        $brand = resolve(BrandSettings::class);
        $brand->logo_type = LogoType::LogoText;
        $brand->logo_text_path = 'branding/custom-logo.png';
        $brand->color = BrandColor::from($color);
        $brand->save();
        $settings = resolve(MailSettings::class);
        $settings->show_logo = true;
        $settings->save();
        $html = (string) resolve(GetMailPreview::class)->handle()->render();

        expect($html)->toContain('branding/custom-logo.png')->toContain($accent);
    })->with([
        ['blue', '#2563eb'], ['green', '#198b46'], ['purple', '#7c3aed'],
        ['rose', '#e11d48'], ['orange', '#d96306'], ['neutral', '#171717'],
    ]);
});

describe('Mail logo selection', function (): void {
    it('uses the icon asset when the brand selects a logo without text', function (): void {
        $brand = resolve(BrandSettings::class);
        $brand->logo_type = LogoType::LogoOnly;
        $brand->logo_path = 'branding/custom-icon.png';
        $brand->save();
        $html = (string) resolve(GetMailPreview::class)->handle()->render();

        expect($html)->toContain('branding/custom-icon.png')->not->toContain('class="brand-name"');
    });
});
