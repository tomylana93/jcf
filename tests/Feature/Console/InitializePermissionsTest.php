<?php

declare(strict_types=1);

use App\Enums\Permission as CorePermission;
use App\Enums\Role as CoreRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

use function Pest\Laravel\artisan;

describe('Permission initialization', function (): void {
    it('creates enum roles idempotently without removing direct permissions or custom roles', function (): void {
        $user = User::factory()->create();
        $permission = Permission::factory()->create();
        $customRole = Role::factory()->create();
        $customRole->givePermissionTo($permission);
        $user->assignRole($customRole);
        $user->givePermissionTo($permission);
        $adminRole = Role::factory()->create(['name' => CoreRole::SuperAdmin->value]);
        $adminRole->givePermissionTo($permission);

        artisan('init:permission')->expectsOutput('Roles and permissions initialized.')->assertSuccessful();
        artisan('init:permission')->assertSuccessful();

        expect(Role::query()->count())->toBe(2)
            ->and($adminRole->fresh()->permissions)->toBeEmpty()
            ->and($user->fresh()->getDirectPermissions()->modelKeys())->toBe([$permission->id])
            ->and($user->fresh()->getRoleNames()->all())->toBe([$customRole->name])
            ->and($customRole->fresh()->permissions->modelKeys())->toBe([$permission->id])
            ->and(Permission::query()->count())->toBe(11)
            ->and($user->fresh()->can(CorePermission::ManageUsers))->toBeFalse();
    });

    it('creates the missing superadmin role', function (): void {
        artisan('init:permission')->assertSuccessful();

        expect(Role::query()->sole()->name)->toBe('superadmin')
            ->and(Role::query()->sole()->guard_name)->toBe('web');
    });

    it('creates missing core permissions and preserves their identifiers on repeat runs', function (): void {
        $existing = Permission::factory()->create(['name' => CorePermission::ViewUsers->value]);

        artisan('init:permission')->assertSuccessful();

        $identifiers = Permission::query()->orderBy('name')->pluck('id', 'name')->all();

        artisan('init:permission')->assertSuccessful();

        expect(Permission::query()->orderBy('name')->pluck('id', 'name')->all())->toBe($identifiers)
            ->and(Permission::findByName('users.view')->id)->toBe($existing->id)
            ->and(Permission::query()->pluck('name')->all())->toEqualCanonicalizing([
                'backups.manage', 'system.view', 'audit.view', 'settings.view', 'settings.manage', 'users.view', 'users.manage', 'users.permissions.assign', 'documentation.view', 'documentation.manage',
            ])
            ->and(Permission::query()->where('guard_name', '!=', 'web')->exists())->toBeFalse();
    });
});
