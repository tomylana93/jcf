<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as CoreRole;
use App\Models\AuditLog;
use App\Models\Permission as PermissionModel;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

describe('direct user permissions', function (): void {
    it('enforces the permission management policy for each actor and target', function (bool $superAdmin, bool $disabled, bool $protectedTarget, bool $expected): void {
        $actor = User::factory()->create(['status' => $disabled ? 'disabled' : 'active']);
        if ($superAdmin) {
            $actor->assignRole(CoreRole::SuperAdmin);
        } else {
            assignRolePermissions($actor, Permission::AssignUserPermissions);
        }
        $target = User::factory()->create();
        if ($protectedTarget) {
            $target->assignRole(CoreRole::SuperAdmin);
        }

        expect($actor->can('viewPermissions', User::class))->toBe($superAdmin && ! $disabled)
            ->and($actor->can('assignPermissions', $target))->toBe($expected);
    })->with([
        'active superadmin' => [true, false, false, true],
        'protected superadmin' => [true, false, true, false],
        'disabled superadmin' => [true, true, false, false],
        'role granted actor' => [false, false, false, false],
    ]);

    it('lists searchable users with separate direct and inherited access', function (): void {
        $actor = User::factory()->create();
        $actor->assignRole(CoreRole::SuperAdmin);
        $target = User::factory()->create(['name' => 'Permission Target']);
        assignRolePermissions($target, Permission::ViewSettings);
        $target->givePermissionTo(PermissionModel::findOrCreate('users.view', 'web'));

        actingAs($actor);
        get(route('permissions.index', ['filter' => ['search' => 'permission target']]))
            ->assertInertia(fn (Assert $page): Assert => $page->component('user-permissions/Index')
                ->has('users.data', 1)->where('users.data.0.id', $target->id)
                ->where('users.data.0.direct_permissions', ['users.view'])
                ->where('users.data.0.inherited_permissions', ['settings.view'])
                ->where('users.data.0.super_admin', false));
    });

    it('replaces only additional permissions and records the actor and notification', function (): void {
        $actor = User::factory()->create();
        $actor->assignRole(CoreRole::SuperAdmin);
        $target = User::factory()->create();
        assignRolePermissions($target, Permission::ViewSettings);
        $roleId = $target->roles->sole()->id;
        $target->givePermissionTo(PermissionModel::findOrCreate('users.view', 'web'));
        PermissionModel::findOrCreate('audit.view', 'web');
        Log::spy();
        actingAs($actor);

        patch(route('permissions.update', $target), [
            'permissions' => ['settings.view', 'audit.view'], 'role' => 'superadmin', 'status' => 'disabled',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $target->refresh();
        expect($target->getDirectPermissions()->pluck('name')->all())->toBe(['audit.view'])
            ->and($target->roles->sole()->id)->toBe($roleId)
            ->and($target->status->value)->toBe('active')
            ->and($target->can(Permission::ViewSettings))->toBeTrue()
            ->and($target->can(Permission::ViewUsers))->toBeFalse();
        $audit = AuditLog::query()->where('event', 'user.permissions_updated')->sole();
        expect($audit->actor_id)->toBe($actor->id)
            ->and($audit->subject_id)->toBe($target->id)
            ->and($audit->before)->toBe(['permissions' => ['users.view']])
            ->and($audit->after)->toBe(['permissions' => ['audit.view']])
            ->and($target->notifications()->sole()->data)->toBe(['kind' => 'permissions_updated', 'subject' => '']);
        Log::shouldHaveReceived('info')->once()->with('User direct permissions updated', [
            'actor_id' => $actor->id, 'user_id' => $target->id,
            'before' => ['users.view'], 'after' => ['audit.view'], 'changed_at' => now()->toIso8601String(),
        ]);
    });

    it('clears direct access without removing role permissions', function (): void {
        $actor = User::factory()->create();
        $actor->assignRole(CoreRole::SuperAdmin);
        $target = User::factory()->create();
        assignRolePermissions($target, Permission::ViewSettings);
        $target->givePermissionTo(PermissionModel::findOrCreate('users.view', 'web'));
        actingAs($actor);

        patch(route('permissions.update', $target), ['permissions' => []])->assertRedirect();

        expect($target->fresh()->getDirectPermissions())->toBeEmpty()
            ->and($target->fresh()->can(Permission::ViewSettings))->toBeTrue()
            ->and($target->fresh()->can(Permission::ViewUsers))->toBeFalse();
    });

    it('does not audit or notify when the permission set is unchanged', function (): void {
        $actor = User::factory()->create();
        $actor->assignRole(CoreRole::SuperAdmin);
        $target = User::factory()->create();
        $target->givePermissionTo(PermissionModel::findOrCreate('users.view', 'web'));
        Log::spy();
        actingAs($actor);

        patch(route('permissions.update', $target), ['permissions' => ['users.view']])->assertRedirect();

        expect(AuditLog::query()->count())->toBe(0)
            ->and($target->notifications()->count())->toBe(0);
        Log::shouldNotHaveReceived('info');
    });

    it('rejects invalid permission input without changing access', function (array $payload, string $error): void {
        $actor = User::factory()->create();
        $actor->assignRole(CoreRole::SuperAdmin);
        $target = User::factory()->create();
        $target->givePermissionTo(PermissionModel::findOrCreate('users.view', 'web'));
        actingAs($actor);

        patch(route('permissions.update', $target), $payload)->assertSessionHasErrors($error);

        expect($target->fresh()->getDirectPermissions()->pluck('name')->all())->toBe(['users.view'])
            ->and(AuditLog::query()->count())->toBe(0)
            ->and($target->notifications()->count())->toBe(0);
    })->with([
        'missing' => [[], 'permissions'],
        'not array' => [['permissions' => 'users.view'], 'permissions'],
        'not list' => [['permissions' => ['custom' => 'users.view']], 'permissions'],
        'reserved' => [['permissions' => ['users.permissions.assign']], 'permissions.0'],
        'unknown' => [['permissions' => ['unknown.permission']], 'permissions.0'],
        'duplicate' => [['permissions' => ['users.view', 'users.view']], 'permissions.0'],
        'non string' => [['permissions' => [123]], 'permissions.0'],
    ]);

    it('offers only initialized web enum permissions not inherited from the role', function (): void {
        $actor = User::factory()->create();
        $actor->assignRole(CoreRole::SuperAdmin);
        $target = User::factory()->create();
        assignRolePermissions($target, Permission::ViewSettings);
        PermissionModel::findOrCreate('users.view', 'web');
        PermissionModel::findOrCreate('legacy.permission', 'web');
        PermissionModel::findOrCreate('audit.view', 'api');
        PermissionModel::findOrCreate('users.permissions.assign', 'web');
        actingAs($actor);

        get(route('permissions.edit', $target))->assertInertia(fn (Assert $page): Assert => $page
            ->component('user-permissions/Edit')->has('permissions', 1)
            ->where('permissions.0.value', 'users.view')->where('user.inherited_permissions', ['settings.view']));
        foreach (['system.view', 'legacy.permission', 'audit.view'] as $value) {
            patch(route('permissions.update', $target), ['permissions' => [$value]])->assertSessionHasErrors('permissions.0');
        }
        expect($target->fresh()->getDirectPermissions())->toBeEmpty();
    });

    it('denies all endpoints to an ordinary account despite assignment grants', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::AssignUserPermissions);
        $actor->givePermissionTo(Permission::AssignUserPermissions);
        $target = User::factory()->create();
        actingAs($actor);

        get(route('permissions.index'))->assertForbidden();
        get(route('permissions.edit', $target))->assertForbidden();
        patch(route('permissions.update', $target), ['permissions' => []])->assertForbidden();
        expect($target->fresh()->getDirectPermissions())->toBeEmpty();
    });

    it('shows superadmin access read only and refuses changes', function (): void {
        $actor = User::factory()->create();
        $actor->assignRole(CoreRole::SuperAdmin);
        $target = User::factory()->create();
        $target->assignRole(CoreRole::SuperAdmin);
        actingAs($actor);

        get(route('permissions.edit', $target))->assertInertia(fn (Assert $page): Assert => $page->where('user.super_admin', true));
        patch(route('permissions.update', $target), ['permissions' => []])->assertForbidden();
        expect($target->fresh()->getDirectPermissions())->toBeEmpty();
    });

    it('requires authentication for every permission endpoint', function (): void {
        $target = User::factory()->create();

        get(route('permissions.index'))->assertRedirect(route('login'));
        get(route('permissions.edit', $target))->assertRedirect(route('login'));
        patch(route('permissions.update', $target), ['permissions' => []])->assertRedirect(route('login'));
    });

    it('rejects a disabled superadmin session before saving', function (): void {
        $actor = User::factory()->disabled()->create();
        $actor->assignRole(CoreRole::SuperAdmin);
        $target = User::factory()->create();
        actingAs($actor);

        patch(route('permissions.update', $target), ['permissions' => []])->assertRedirect(route('login'));

        assertGuest();
        expect($target->fresh()->getDirectPermissions())->toBeEmpty();
    });
});
