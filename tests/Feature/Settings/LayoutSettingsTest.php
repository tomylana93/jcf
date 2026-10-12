<?php

declare(strict_types=1);

use App\Enums\AppLayout;
use App\Enums\AuthLayout;
use App\Enums\Permission;
use App\Models\User;
use App\Settings\LayoutSettings;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

describe('Layout settings', function (): void {
    it('redirects guests and forbids users without settings permission', function (): void {
        get(route('settings.layout.edit'))->assertRedirect(route('login'));
        patch(route('settings.layout.update'), [])->assertRedirect(route('login'));

        $user = User::factory()->create();
        actingAs($user)->get(route('settings.layout.edit'))->assertForbidden();
        patch(route('settings.layout.update'), ['app_layout' => 'header', 'auth_layout' => 'card'])->assertForbidden();
    });

    it('shows saved choices and enum options to an administrator', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);

        actingAs($user)->get(route('settings.layout.edit'))->assertInertia(fn (Assert $page): Assert => $page
            ->component('settings/Layout')
            ->where('settings.app_layout', 'sidebar')
            ->where('settings.auth_layout', 'split')
            ->has('appLayoutOptions', 2)
            ->has('authLayoutOptions', 3));
    });

    it('lets users with view permission inspect layouts but not change them', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings);

        actingAs($user)->get(route('settings.layout.edit'))->assertInertia(fn (Assert $page): Assert => $page
            ->component('settings/Layout')
            ->where('settings.app_layout', 'sidebar'));
        patch(route('settings.layout.update'), ['app_layout' => 'header', 'auth_layout' => 'card'])->assertForbidden();
    });

    it('saves both layouts and shares them on application and public pages', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);

        actingAs($user)->patch(route('settings.layout.update'), [
            'app_layout' => 'header',
            'auth_layout' => 'card',
        ])->assertSessionHasNoErrors()->assertRedirect(route('settings.layout.edit'));

        $settings = resolve(LayoutSettings::class)->refresh();
        expect($settings->app_layout)->toBe(AppLayout::Header)
            ->and($settings->auth_layout)->toBe(AuthLayout::Card);
        get(route('settings.layout.edit'))->assertInertia(fn (Assert $page): Assert => $page
            ->where('layout.app_layout', 'header')
            ->where('layout.auth_layout', 'card'));
        auth()->logout();
        get(route('login'))->assertInertia(fn (Assert $page): Assert => $page
            ->where('layout.app_layout', 'header')
            ->where('layout.auth_layout', 'card'));
    });

    it('rejects invalid choices without changing saved layouts', function (array $data, string $field): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewSettings, Permission::ManageSettings);

        actingAs($user)->patch(route('settings.layout.update'), $data)->assertInvalid([$field]);

        $settings = resolve(LayoutSettings::class)->refresh();
        expect($settings->app_layout)->toBe(AppLayout::Sidebar)
            ->and($settings->auth_layout)->toBe(AuthLayout::Split);
    })->with([
        'unknown application layout' => [['app_layout' => 'unknown', 'auth_layout' => 'card'], 'app_layout'],
        'unknown auth layout' => [['app_layout' => 'header', 'auth_layout' => 'unknown'], 'auth_layout'],
        'missing application layout' => [['auth_layout' => 'card'], 'app_layout'],
        'missing auth layout' => [['app_layout' => 'header'], 'auth_layout'],
    ]);
});
