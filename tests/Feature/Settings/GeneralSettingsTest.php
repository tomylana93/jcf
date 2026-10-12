<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\User;
use App\Settings\GeneralSettings;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

describe('General settings', function (): void {
    it('initializes support email from superadmin configuration', function (): void {
        config(['superadmin.email' => 'initial@example.com']);
        $migration = require glob(database_path('settings/*create_general_settings.php'))[0];
        $migration->down();

        $migration->up();

        expect(resolve(GeneralSettings::class)->refresh()->support_email)->toBe('initial@example.com')
            ->and(resolve(GeneralSettings::class)->public_pages_enabled)->toBeFalse();
    });

    it('redirects guests to login', function (): void {
        get(route('settings.general.edit'))->assertRedirect(route('login'));
        patch(route('settings.general.update'), [])->assertRedirect(route('login'));
    });

    it('rejects unverified users before settings access', function (): void {
        $user = User::factory()->unverified()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);

        actingAs($user)->get(route('settings.general.edit'))->assertRedirect(route('verification.notice'));
        patch(route('settings.general.update'), [])->assertRedirect(route('verification.notice'));
    });

    it('enforces read and write permissions', function (bool $allowed): void {
        $user = User::factory()->create();
        if ($allowed) {
            assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);
        }

        expect($user->can(Permission::ViewSettings))->toBe($allowed)
            ->and($user->can(Permission::ManageSettings))->toBe($allowed);
    })->with([
        'without settings permissions' => [false],
        'with settings permissions' => [true],
    ]);

    it('forbids users without permission and preserves saved settings', function (): void {
        $user = User::factory()->create();
        $name = resolve(GeneralSettings::class)->site_name;

        actingAs($user)->get(route('settings.general.edit'))->assertForbidden();
        patch(route('settings.general.update'), ['site_name' => 'Unauthorized', 'site_description' => '', 'support_email' => null, 'public_pages_enabled' => false])->assertForbidden();

        expect(resolve(GeneralSettings::class)->refresh()->site_name)->toBe($name);
        actingAs($user)->withHeaders(['Precognition' => 'true'])->patch(route('settings.general.update'), [])->assertForbidden();
    });

    it('shows settings and configured support email with read-only access', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings);
        config(['superadmin.email' => 'support@example.com']);
        $name = resolve(GeneralSettings::class)->site_name;

        actingAs($user)->get(route('settings.general.edit'))->assertInertia(fn (Assert $page): Assert => $page
            ->component('settings/General')
            ->where('settings.site_name', $name)
            ->where('settings.site_description', 'A modern, modular starterkit for building simple, purposeful applications.')
            ->where('settings.support_email', resolve(GeneralSettings::class)->support_email)
            ->where('settings.public_pages_enabled', false)
            ->where('auth.permissions', ['settings.view']));
        patch(route('settings.general.update'), ['site_name' => 'Unauthorized', 'site_description' => '', 'support_email' => null, 'public_pages_enabled' => false])->assertForbidden();
    });

    it('keeps the stored support contact when configuration changes', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);
        $settings = resolve(GeneralSettings::class);
        $settings->support_email = 'stored@example.com';
        $settings->save();
        config(['superadmin.email' => null]);

        actingAs($user)->get(route('settings.general.edit'))->assertInertia(fn (Assert $page): Assert => $page->where('settings.support_email', 'stored@example.com'));
    });

    it('saves settings and returns the updated global brand after redirect', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);
        config(['superadmin.email' => 'support@example.com']);

        actingAs($user)->patch(route('settings.general.update'), [
            'site_name' => 'New Brand',
            'site_description' => 'Application description',
            'support_email' => 'override@example.com', 'public_pages_enabled' => false,
        ])->assertSessionHasNoErrors()->assertRedirect(route('settings.general.edit'));

        assertDatabaseHas('settings', ['group' => 'general', 'name' => 'site_name', 'payload' => '"New Brand"']);
        assertDatabaseHas('settings', ['group' => 'general', 'name' => 'site_description', 'payload' => '"Application description"']);
        assertDatabaseHas('settings', ['group' => 'general', 'name' => 'support_email', 'payload' => '"override@example.com"']);
        get(route('settings.general.edit'))->assertInertia(fn (Assert $page): Assert => $page
            ->where('general.site_name', 'New Brand')
            ->where('settings.site_name', 'New Brand')
            ->where('settings.support_email', 'override@example.com'));
    });

    it('normalizes an empty description', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);

        actingAs($user)->patch(route('settings.general.update'), ['site_name' => 'Brand', 'site_description' => null, 'support_email' => null, 'public_pages_enabled' => false])
            ->assertSessionHasNoErrors()->assertRedirect(route('settings.general.edit'));

        assertDatabaseHas('settings', ['group' => 'general', 'name' => 'site_description', 'payload' => '""']);
    });

    it('rejects invalid input without changing the brand', function (array $data, string $field, string $message): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);
        $name = resolve(GeneralSettings::class)->site_name;

        actingAs($user)->patch(route('settings.general.update'), $data)->assertInvalid([$field => $message]);

        expect(resolve(GeneralSettings::class)->refresh()->site_name)->toBe($name);
    })->with([
        'invalid email' => [['site_name' => 'Brand', 'site_description' => '', 'support_email' => 'invalid', 'public_pages_enabled' => false], 'support_email', 'valid email'],
        'non-string email' => [['site_name' => 'Brand', 'site_description' => '', 'support_email' => ['invalid'], 'public_pages_enabled' => false], 'support_email', 'string'],
        'long email' => [['site_name' => 'Brand', 'site_description' => '', 'support_email' => str_repeat('a', 243).'@example.com', 'public_pages_enabled' => false], 'support_email', '254'],
        'missing email' => [['site_name' => 'Brand', 'site_description' => ''], 'support_email', 'present'],
        'missing name' => [['site_description' => '', 'support_email' => null, 'public_pages_enabled' => false], 'site_name', 'required'],
        'blank name' => [['site_name' => '   ', 'site_description' => '', 'support_email' => null, 'public_pages_enabled' => false], 'site_name', 'required'],
        'non-string name' => [['site_name' => ['invalid'], 'site_description' => '', 'support_email' => null, 'public_pages_enabled' => false], 'site_name', 'string'],
        'long name' => [['site_name' => str_repeat('a', 101), 'site_description' => '', 'support_email' => null, 'public_pages_enabled' => false], 'site_name', '100'],
        'missing description' => [['site_name' => 'Brand'], 'site_description', 'present'],
        'non-string description' => [['site_name' => 'Brand', 'site_description' => ['invalid'], 'support_email' => null, 'public_pages_enabled' => false], 'site_description', 'string'],
        'long description' => [['site_name' => 'Brand', 'site_description' => str_repeat('a', 501), 'support_email' => null, 'public_pages_enabled' => false], 'site_description', '500'],
        'missing public mode' => [['site_name' => 'Brand', 'site_description' => '', 'support_email' => null], 'public_pages_enabled', 'required'],
        'invalid public mode' => [['site_name' => 'Brand', 'site_description' => '', 'support_email' => null, 'public_pages_enabled' => 'enabled'], 'public_pages_enabled', 'true or false'],
    ]);

    it('validates with precognition without persisting changes', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);
        $name = resolve(GeneralSettings::class)->site_name;

        actingAs($user)->withHeaders(['Precognition' => 'true'])->patch(route('settings.general.update'), [
            'site_name' => 'Preview Brand', 'site_description' => 'Preview description', 'support_email' => 'preview@example.com', 'public_pages_enabled' => false,
        ])->assertSuccessfulPrecognition();

        expect(resolve(GeneralSettings::class)->refresh()->site_name)->toBe($name);
        assertDatabaseHas('settings', ['group' => 'general', 'name' => 'site_description', 'payload' => '"A modern, modular starterkit for building simple, purposeful applications."']);
    });

    it('returns precognition errors without changing settings', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);
        $name = resolve(GeneralSettings::class)->site_name;

        actingAs($user)->withHeaders(['Precognition' => 'true', 'Accept' => 'application/json', 'Precognition-Validate-Only' => 'site_name'])
            ->patch(route('settings.general.update'), ['site_name' => ''])->assertInvalid(['site_name' => 'required']);

        expect(resolve(GeneralSettings::class)->refresh()->site_name)->toBe($name);
    });

    it('shares the saved name on public pages and escapes the initial title', function (): void {
        $settings = resolve(GeneralSettings::class);
        $settings->site_name = '<script>Brand</script>';
        $settings->site_description = 'Modern modular starterkit';
        $settings->support_email = 'support@example.com';
        $settings->save();

        get(route('login'))->assertInertia(fn (Assert $page): Assert => $page->where('general.site_name', '<script>Brand</script>')
            ->where('general.site_description', 'Modern modular starterkit')
            ->where('general.support_email', 'support@example.com'))
            ->assertSee('<title>&lt;script&gt;Brand&lt;/script&gt;</title>', false);
    });
});
