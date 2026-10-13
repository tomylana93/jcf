<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as CoreRole;
use App\Models\Permission as PermissionModel;
use App\Models\User;

use function Pest\Laravel\actingAs;

describe('User permission browser flows', function (): void {
    it('filters, resets, saves and clears additional access while retaining role access', function (): void {
        $actor = User::factory()->create();
        $actor->assignRole(CoreRole::SuperAdmin);
        $target = User::factory()->create(['name' => 'Browser Permission Target']);
        assignRolePermissions($target, Permission::ViewSettings);
        PermissionModel::findOrCreate('users.view', 'web');
        PermissionModel::findOrCreate('audit.view', 'web');
        actingAs($actor);

        $page = visit(route('permissions.index', absolute: false));

        $page->assertSee(__('permission.card.description'))
            ->fill('[aria-label="'.__('table.label.search').'"]', $target->email)
            ->assertDontSee($actor->email)
            ->click('main a[href="'.route('permissions.edit', $target, absolute: false).'"]')
            ->assertSee(__('permission.helper.inherited'))
            ->assertSee('settings.view')
            ->assertMissing('[id="permission-settings.view"]')
            ->fill('#permission-search', 'users.view')
            ->assertMissing('[id="permission-audit.view"]')
            ->click(__('permission.button.select_visible'))
            ->assertChecked('[id="permission-users.view"]')
            ->click(__('permission.button.reset'))
            ->assertNotChecked('[id="permission-users.view"]')
            ->click('[id="permission-users.view"]')
            ->click('@save-permissions')
            ->assertSee(__('permission.status.updated'))
            ->assertChecked('[id="permission-users.view"]')
            ->assertDisabled('@save-permissions')
            ->assertNoJavaScriptErrors()
            ->screenshot(filename: 'session-one-permissions-desktop');

        expect($target->fresh()->getDirectPermissions()->pluck('name')->all())->toBe(['users.view'])
            ->and($target->fresh()->can(Permission::ViewSettings))->toBeTrue();

        $page->click(__('permission.button.clear_visible'))
            ->assertNotChecked('[id="permission-users.view"]')
            ->click('@save-permissions')
            ->assertSee(__('permission.status.updated'))
            ->assertDisabled('@save-permissions')
            ->assertNoJavaScriptErrors();

        expect($target->fresh()->getDirectPermissions())->toBeEmpty()
            ->and($target->fresh()->can(Permission::ViewSettings))->toBeTrue();
    });

    it('supports permission selection on mobile and hides navigation from ordinary accounts', function (): void {
        $actor = User::factory()->create();
        $actor->assignRole(CoreRole::SuperAdmin);
        $target = User::factory()->create();
        PermissionModel::findOrCreate('users.view', 'web');
        actingAs($actor);

        visit(route('permissions.edit', $target, absolute: false))->on()->mobile()
            ->click('[id="permission-users.view"]')
            ->click('@save-permissions')
            ->assertSee(__('permission.status.updated'))
            ->assertNoJavaScriptErrors()
            ->screenshot(filename: 'session-one-permissions-mobile');

        expect($target->fresh()->can(Permission::ViewUsers))->toBeTrue();

        actingAs($target);

        visit(route('dashboard', absolute: false))
            ->assertMissing('nav a[href="'.route('permissions.index', absolute: false).'"]')
            ->assertNoJavaScriptErrors();
    });
});
