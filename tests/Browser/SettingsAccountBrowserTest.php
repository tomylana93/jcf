<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\User;
use App\Settings\GeneralSettings;

use function Pest\Laravel\actingAs;

describe('Basic settings and account browser flows', function (): void {
    it('saves general settings through the typed form', function (): void {
        $user = actingAsUserWithPermissions(Permission::ViewSettings, Permission::ManageSettings);

        visit(route('settings.general.edit', absolute: false))
            ->fill('#site_name', 'JCF Browser')
            ->fill('#site_description', 'Application settings')
            ->click('@save-general-settings')
            ->assertSee(__('settings.general.status.updated'))
            ->assertNoJavaScriptErrors()
            ->screenshot(filename: 'stage-two-settings-desktop');

        expect(resolve(GeneralSettings::class)->refresh()->site_name)->toBe('JCF Browser');
    });

    it('renders account forms on mobile without JavaScript errors', function (): void {
        actingAs(User::factory()->create());

        visit(route('profile.edit', absolute: false))->on()->mobile()
            ->assertSee(__('account.profile.title'))
            ->assertVisible('#name')
            ->assertNoJavaScriptErrors()
            ->screenshot(filename: 'stage-two-account-mobile');
    });

    it('persists the selected locale through navigation', function (): void {
        $page = visit(route('login', absolute: false));

        $page->click('[aria-label="'.__('locale.label.change').'"]')
            ->click('[role="menuitemradio"][aria-label="Indonesian"]')
            ->assertSee('Lupa password?')
            ->navigate(route('password.request', absolute: false))
            ->assertSee('Lupa password')
            ->assertNoJavaScriptErrors();
    });
});
