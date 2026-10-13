<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\Permission as PermissionModel;
use App\Models\User;
use App\Settings\BrandSettings;
use App\Settings\GeneralSettings;

use function Pest\Laravel\actingAs;

describe('Shell synchronization', function (): void {
    it('restores translations and document language through browser history', function (): void {
        actingAs(User::factory()->create());
        $page = visit(route('dashboard'));
        $page->click('[data-test="sidebar-menu-button"]')
            ->click('a[href="'.route('profile.edit', absolute: false).'"]')
            ->assertPathIs('/account/profile')
            ->click('[aria-label="'.__('locale.label.change').'"]')
            ->click('[role="menuitemradio"][aria-label="Indonesian"]')
            ->assertScript('document.documentElement.lang', 'id');
        $page->back()->assertPathIs('/dashboard')->assertScript('document.documentElement.lang', 'en')
            ->assertSee('Dashboard');
        $page->forward()->assertPathIs('/account/profile')->assertScript('document.documentElement.lang', 'id')
            ->assertSee('Profil')->assertNoJavaScriptErrors();
    });

    it('updates favicon and restores the default without duplicate icons across navigation and history', function (): void {
        $user = User::factory()->create();
        actingAs($user);
        $page = visit(route('dashboard'));
        $page->assertScript('document.querySelectorAll(\'link[rel="icon"]\').length', 1);
        $settings = resolve(BrandSettings::class);
        $settings->favicon_path = 'brand/browser-favicon.png';
        $settings->save();

        $page->click('[data-test="sidebar-menu-button"]')
            ->click('a[href="'.route('profile.edit', absolute: false).'"]')
            ->assertPathIs('/account/profile')
            ->assertScript('new URL(document.querySelector(\'link[rel="icon"]\').href).pathname', '/storage/brand/browser-favicon.png');
        $page->back()->assertPathIs('/dashboard')
            ->assertScript('new URL(document.querySelector(\'link[rel="icon"]\').href).pathname', '/favicon.svg');
        $page->forward()->assertPathIs('/account/profile')
            ->assertScript('new URL(document.querySelector(\'link[rel="icon"]\').href).pathname', '/storage/brand/browser-favicon.png');
        $settings->favicon_path = null;
        $settings->save();
        $page->click('[data-sidebar="menu-button"][data-size="default"][href="/dashboard"]')->assertPathIs('/dashboard')
            ->assertScript('new URL(document.querySelector(\'link[rel="icon"]\').href).pathname', '/favicon.svg')
            ->assertScript('document.querySelectorAll(\'link[rel="icon"]\').length', 1)
            ->assertNoJavaScriptErrors();
    });

    it('discards prefetched navigation after settings and profile mutations', function (): void {
        $user = User::factory()->create(['name' => 'Before Profile']);
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);
        actingAs($user);
        $page = visit(route('settings.general.edit'));
        $page->script('() => { window.prefetchedDashboard = false; document.addEventListener("inertia:prefetched", event => { if (event.detail.visit.url.pathname === "/dashboard") window.prefetchedDashboard = true; }); }');
        $page->hover('[data-sidebar="menu-button"][data-size="default"][href="/dashboard"]')
            ->assertScript('window.prefetchedDashboard', true)
            ->fill('#site_name', 'Shell Settings Updated')
            ->fill('#site_description', 'Updated shell settings')
            ->click('@save-general-settings')->assertSee(__('settings.general.status.updated'))
            ->click('[data-sidebar="menu-button"][data-size="default"][href="/dashboard"]')->assertPathIs('/dashboard')
            ->assertScript('document.title.includes("Shell Settings Updated")', true);
        expect(resolve(GeneralSettings::class)->refresh()->site_name)->toBe('Shell Settings Updated');

        $page->click('[data-test="sidebar-menu-button"]')
            ->click('a[href="'.route('profile.edit', absolute: false).'"]')->assertPathIs('/account/profile');
        $page->script('() => { window.prefetchedDashboard = false; }');
        $page->hover('[data-sidebar="menu-button"][data-size="default"][href="/dashboard"]')->assertScript('window.prefetchedDashboard', true)
            ->assertMissing('[data-sidebar="menu-button"][href="/master-data"]');
        $user->givePermissionTo(PermissionModel::findOrCreate(Permission::ViewUsers->value, 'web'));
        $page
            ->fill('#name', 'After Profile')->click('@update-profile-button')->assertSee(__('account.profile.status.updated'))
            ->click('[data-sidebar="menu-button"][data-size="default"][href="/dashboard"]')->assertPathIs('/dashboard')
            ->assertSee('After Profile')->assertDontSee('Before Profile')
            ->assertVisible('[data-sidebar="menu-button"][href="/master-data"]')->assertNoJavaScriptErrors();
        expect($user->fresh()->name)->toBe('After Profile');
    });
});
