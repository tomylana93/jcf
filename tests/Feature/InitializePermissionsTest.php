<?php

declare(strict_types=1);

use App\Enums\Role as CoreRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

use function Pest\Laravel\artisan;

describe('permission initialization', function (): void {
    it('creates enum roles idempotently without removing direct permissions or custom roles', function (): void {
        $user = User::factory()->create();
        $permission = Permission::factory()->create();
        $customRole = Role::factory()->create();
        $user->assignRole($customRole);
        $user->givePermissionTo($permission);
        $adminRole = Role::factory()->create(['name' => CoreRole::SuperAdmin->value]);
        $adminRole->givePermissionTo($permission);

        artisan('init:permission')->expectsOutput('Roles and permissions initialized.')->assertSuccessful();
        artisan('init:permission')->assertSuccessful();

        expect(Role::query()->count())->toBe(2)
            ->and($adminRole->fresh()->permissions)->toBeEmpty()
            ->and($user->fresh()->getDirectPermissions()->modelKeys())->toBe([$permission->id])
            ->and($user->fresh()->getRoleNames()->all())->toBe([$customRole->name]);
    });

    it('creates the missing superadmin role', function (): void {
        artisan('init:permission')->assertSuccessful();

        expect(Role::query()->sole()->name)->toBe('superadmin')
            ->and(Role::query()->sole()->guard_name)->toBe('web');
    });
});
