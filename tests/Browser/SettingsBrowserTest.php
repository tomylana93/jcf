<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Jobs\MasterData\User\SendUserInvitation;
use App\Models\User;
use App\Settings\AuthSettings;
use App\Settings\MailSettings;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;

describe('Mail settings in the browser', function (): void {
    it('saves SMTP and layout choices then updates the email preview', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);

        actingAs($user);

        visit(route('settings.index'))
            ->click('@mail-settings-card')
            ->assertUrlIs(route('settings.mail.edit'))
            ->select('#mailer', 'smtp')
            ->fill('#smtp_host', 'smtp.mailtrap.io')
            ->fill('#smtp_port', '2525')
            ->fill('#smtp_username', 'mailtrap-user')
            ->fill('#smtp_password', 'mailtrap-password')
            ->fill('#from_address', 'hello@example.test')
            ->fill('#from_name', 'Browser Mail')
            ->fill('#footer_text', 'Browser footer text')
            ->click('@save-mail-settings')
            ->assertUrlIs(route('settings.mail.edit'))
            ->assertValue('#smtp_host', 'smtp.mailtrap.io')
            ->assertSee('A password is saved.')
            ->withinFrame('#mail-preview', fn ($frame) => $frame->assertSee('Browser footer text'))
            ->assertNoJavaScriptErrors();

        $settings = resolve(MailSettings::class)->refresh();

        expect($settings->mailer)->toBe('smtp')
            ->and($settings->smtp_host)->toBe('smtp.mailtrap.io')
            ->and($settings->smtp_password)->toBe('mailtrap-password')
            ->and($settings->footer_text)->toBe('Browser footer text');
    });

    it('submits a test email from the settings page in log mode', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);

        actingAs($user);

        $settings = resolve(MailSettings::class);
        $settings->mailer = 'log';
        $settings->save();

        visit(route('settings.mail.edit'))
            ->fill('#test_email', 'recipient@example.test')
            ->click('@send-mail-test')
            ->assertUrlIs(route('settings.mail.edit'))
            ->assertSee('Test email processed.')
            ->assertNoJavaScriptErrors();
    });
});

describe('Auth settings in the browser', function (): void {
    it('persists the registration switch from the auth settings page', function (): void {
        $user = actingAsUserWithPermissions(Permission::ViewSettings, Permission::ManageSettings);

        visit(route('settings.auth.edit'))->click('#registration_enabled')->click('@save-auth-settings')
            ->assertSee(__('settings.auth.status.updated'))->assertNoJavaScriptErrors();

        expect(resolve(AuthSettings::class)->refresh()->registration_enabled)->toBeFalse();
    });

    it('creates an invited account without showing password fields', function (): void {
        Queue::fake([SendUserInvitation::class]);
        $user = actingAsUserWithPermissions(Permission::ViewUsers, Permission::ManageUsers);

        visit(route('master-data.users.index'))->click(__('common.button.create'))
            ->fill('#user-name', 'Browser Invited')->fill('#user-email', 'browser-invited@example.test')
            ->click('#send-invitation')->assertMissing('#user-password')
            ->click('[role="dialog"] button[type="submit"]')->assertSee(__('user.status.created'))
            ->assertNoJavaScriptErrors();

        expect(User::query()->where('email', 'browser-invited@example.test')->sole()->invited_at)->not->toBeNull();

        Queue::assertPushed(SendUserInvitation::class);
    });
});
