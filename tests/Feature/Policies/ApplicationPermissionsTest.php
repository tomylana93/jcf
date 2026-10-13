<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as CoreRole;
use App\Models\Permission as PermissionModel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

describe('Application permission gates', function (): void {
    it('denies application permissions to ordinary accounts', function (Permission $permission): void {
        $user = User::factory()->create();

        expect($user->can($permission))->toBeFalse();
    })->with(Permission::cases());

    it('grants core permissions to superadmin before permission synchronization', function (Permission $permission): void {
        $user = User::factory()->create();
        $user->assignRole(CoreRole::SuperAdmin);

        expect($user->can($permission))->toBeTrue()
            ->and($user->getAllPermissions())->toBeEmpty();
    })->with(Permission::cases());

    it('grants only the permissions assigned to a database role', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ViewUsers);

        expect($user->can(Permission::ViewUsers))->toBeTrue()
            ->and($user->can(Permission::ManageUsers))->toBeFalse()
            ->and($user->can(Permission::ViewSettings))->toBeFalse();
    });

    it('reserves permission assignment for superadmin despite stored grants', function (bool $direct): void {
        $user = User::factory()->create();
        $permission = PermissionModel::findOrCreate(Permission::AssignUserPermissions->value, 'web');

        if ($direct) {
            $user->givePermissionTo($permission);
        } else {
            assignRolePermissions($user, Permission::AssignUserPermissions);
        }

        expect($user->can(Permission::AssignUserPermissions))->toBeFalse();
    })->with(['direct grant' => true, 'role grant' => false]);

    it('denies application permissions to disabled superadmin accounts', function (Permission $permission): void {
        $user = User::factory()->disabled()->create();
        $user->assignRole(CoreRole::SuperAdmin);

        expect($user->can($permission))->toBeFalse();
    })->with(Permission::cases());

    it('denies inherited permission grants to disabled accounts', function (): void {
        $user = User::factory()->disabled()->create();
        assignRolePermissions($user, Permission::ViewUsers);

        expect($user->can(Permission::ViewUsers))->toBeFalse();
    });

    it('preserves policy arguments and unrelated gate decisions for superadmin', function (): void {
        $user = User::factory()->create();
        $user->assignRole(CoreRole::SuperAdmin);
        Gate::define(Permission::ViewUsers->value, fn (User $actor, User $target): bool => false);
        Gate::define('restricted-action', fn (User $actor): bool => false);
        Gate::define('allowed-action', fn (User $actor): bool => true);

        expect($user->can(Permission::ViewUsers, $user))->toBeFalse()
            ->and($user->can('restricted-action'))->toBeFalse()
            ->and($user->can('allowed-action'))->toBeTrue()
            ->and($user->can('unknown.permission'))->toBeFalse();
    });

    it('revokes inherited access when a role is replaced', function (): void {
        $user = User::factory()->create();
        assignRolePermissions($user, Permission::ManageUsers);
        expect($user->can(Permission::ManageUsers))->toBeTrue();

        $user->syncRoles(Role::factory()->create());

        expect($user->can(Permission::ManageUsers))->toBeFalse();
    });
});

describe('Permission middleware boundary', function (): void {
    beforeEach(function (): void {
        Route::middleware(['web', 'auth', 'verified', 'can:'.Permission::ManageUsers->value])
            ->get('/_test/permission-boundary', fn () => response()->noContent());
    });

    it('redirects unauthenticated requests to login', function (): void {
        get('/_test/permission-boundary')->assertRedirect(route('login'));
    });

    it('returns 403 when the authenticated account lacks permission', function (): void {
        actingAs(User::factory()->create());

        get('/_test/permission-boundary')->assertForbidden();
    });

    it('allows accounts with the required role permission', function (): void {
        $user = actingAsUserWithPermissions(Permission::ManageUsers);

        get('/_test/permission-boundary')->assertNoContent();
    });

    it('allows superadmin without explicit grants', function (): void {
        $user = User::factory()->create();
        $user->assignRole(CoreRole::SuperAdmin);
        actingAs($user);

        get('/_test/permission-boundary')->assertNoContent();
    });

    it('rejects disabled superadmin sessions before authorization', function (): void {
        $user = User::factory()->disabled()->create();
        $user->assignRole(CoreRole::SuperAdmin);
        actingAs($user);

        get('/_test/permission-boundary')->assertRedirect(route('login'));

        assertGuest();
    });
});
