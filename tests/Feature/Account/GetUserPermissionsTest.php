<?php

declare(strict_types=1);

use App\Actions\Account\GetUserPermissions;
use App\Enums\Permission;
use App\Enums\Role as CoreRole;
use App\Models\User;
use App\Settings\ChatSettings;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('Shared application permissions', function (): void {
    it('shares an empty permission list for guests', function (): void {
        get(route('login'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('auth.permissions', [])

        );
    });

    it('keeps dashboard and personal settings available without application permissions', function (): void {
        $user = User::factory()->create();
        actingAs($user);

        get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('auth.permissions', [])

        );

        get(route('profile.edit'))->assertOk();
        get(route('sessions.index'))->assertRedirect(route('password.confirm'));
    });

    it('shares only effective permissions evaluated by the server gate', function (): void {
        $user = actingAsUserWithPermissions(Permission::ViewUsers, Permission::AssignUserPermissions);

        get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('auth.permissions', ['users.view'])

        );
    });

    it('shares all core permissions for superadmin without database grants', function (): void {
        $user = User::factory()->create();
        $user->assignRole(CoreRole::SuperAdmin);
        actingAs($user);

        get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('auth.permissions', [
                'chat.use', 'system.view', 'backups.manage', 'audit.view', 'settings.view', 'settings.manage', 'users.view', 'users.manage', 'users.permissions.assign', 'documentation.view', 'documentation.manage', 'website.content.edit', 'website.content.publish', 'website.configure', 'website.inbox.view',
            ])

        );
    });

    it('returns no effective permissions for disabled accounts', function (): void {
        $user = User::factory()->disabled()->create();
        $user->assignRole(CoreRole::SuperAdmin);

        expect(resolve(GetUserPermissions::class)->handle($user))->toBeEmpty();
    });

    it('filters permissions from inactive modules', function (bool $hard): void {
        if ($hard) {
            config(['features.chat' => false]);
        } else {
            resolve(ChatSettings::class)->fill(['enabled' => false])->save();
        }

        actingAsUserWithPermissions(Permission::ViewSettings, Permission::UseChat);

        get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('auth.permissions', ['settings.view'])

        );
    })->with([true, false]);
});
