<?php

declare(strict_types=1);

use App\Enums\Role as CoreRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Exceptions\GuardDoesNotMatch;

describe('single role users', function (): void {
    it('creates users without inherited access', function (): void {
        $user = User::factory()->create();

        expect($user->roles)->toBeEmpty()
            ->and($user->getAllPermissions())->toBeEmpty();
    });

    it('replaces the role and preserves direct permissions', function (string $method): void {
        $user = User::factory()->create();
        $first = Role::factory()->create();
        $second = Role::factory()->create();
        $direct = Permission::factory()->create();
        $inherited = Permission::factory()->create();
        $first->givePermissionTo($inherited);
        $user->assignRole($first);
        $user->givePermissionTo($direct);

        $user->$method($second->name);

        expect($second->id)->toBeUuid()
            ->and($direct->id)->toBeUuid()
            ->and($user->fresh()->getRoleNames()->all())->toBe([$second->name])
            ->and($user->can($direct->name))->toBeTrue()
            ->and($user->can($inherited->name))->toBeFalse();
    })->with(['assignRole', 'syncRoles']);

    it('rejects multiple roles without changing existing access', function (): void {
        $user = User::factory()->create();
        $first = Role::factory()->create();
        $second = Role::factory()->create();
        $user->assignRole($first);

        expect(fn () => $user->syncRoles([$first, $second]))->toThrow(InvalidArgumentException::class)
            ->and($user->fresh()->getRoleNames()->all())->toBe([$first->name]);
    });

    it('rejects roles from another guard without changing existing access', function (): void {
        $user = User::factory()->create();
        $original = Role::factory()->create();
        $wrongGuard = Role::factory()->create(['guard_name' => 'api']);
        $user->assignRole($original);

        expect(fn () => $user->assignRole($wrongGuard))->toThrow(GuardDoesNotMatch::class)
            ->and($user->fresh()->getRoleNames()->all())->toBe([$original->name]);
    });

    it('rejects assignments before the user is saved', function (): void {
        $user = User::factory()->make();

        expect(fn () => $user->assignRole(CoreRole::SuperAdmin))->toThrow(LogicException::class);
    });

    it('clears the role while retaining direct permissions', function (): void {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();
        $user->assignRole($role);
        $user->givePermissionTo($permission);

        $user->syncRoles([]);

        expect($user->fresh()->roles)->toBeEmpty()
            ->and($user->can($permission->name))->toBeTrue();
    });

    it('grants superadmin registered permissions while respecting gates and policy arguments', function (): void {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'application.publish']);
        Gate::define('restricted-action', fn (User $user): bool => false);
        Gate::define($permission->name, fn (User $user, User $target): bool => false);
        $user->syncRoles(CoreRole::SuperAdmin);

        expect($user->can($permission->name))->toBeTrue()
            ->and($user->can($permission->name, $user))->toBeFalse()
            ->and($user->can('restricted-action'))->toBeFalse()
            ->and($user->can('unknown.permission'))->toBeFalse()
            ->and($user->getAllPermissions())->toBeEmpty();
    });
});
