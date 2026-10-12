<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as SystemRole;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

describe('user management', function (): void {
    it('lists users with safe resource fields and excludes superadmins', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ViewUsers);
        $target = User::factory()->create(['email' => 'found@example.test']);
        $role = Role::factory()->create(['name' => 'editor']);
        $target->syncRoles($role);
        $superadmin = User::factory()->create();
        $superadmin->syncRoles(Role::findOrCreate(SystemRole::SuperAdmin->value, 'web'));

        actingAs($actor)->get(route('master-data.users.index', ['filter' => ['search' => 'FOUND']]))
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('master-data/users/Index')->has('users.data', 1)
                ->where('users.data.0.id', $target->id)
                ->where('users.data.0.email_verified', true)
                ->where('users.data.0.avatar', '')
                ->where('users.data.0.role', ['label' => 'editor', 'color' => 'neutral'])
                ->where('users.data.0.roles.0.name', 'editor')
                ->missing('users.data.0.password')->missing('users.data.0.two_factor_secret')
                ->missing('users.data.0.remember_token'));
        get(route('master-data.users.index'))->assertInertia(fn (Assert $page): Assert => $page->where('users.total', 2));
        get(route('master-data.index'))->assertOk();
    });

    it('filters by role and status and shares the same query for export', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ViewUsers);
        $role = Role::factory()->create(['name' => 'operator']);
        $target = User::factory()->create(['status' => UserStatus::Disabled]);
        $target->syncRoles($role);
        User::factory()->create();
        User::factory()->create(['status' => UserStatus::Disabled]);
        $query = ['filter' => ['role' => 'operator', 'status' => 'disabled'], 'sort' => 'name'];

        actingAs($actor)->get(route('master-data.users.index', $query))->assertInertia(fn (Assert $page): Assert => $page
            ->has('users.data', 1)->where('users.data.0.id', $target->id)
            ->where('users.data.0.status_label', 'Disabled')->where('users.data.0.status_variant', 'secondary'));
        getJson(route('master-data.users.export', $query))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $target->id);
    });

    it('sorts and paginates without loading records from another page', function (): void {
        $actor = User::factory()->create(['name' => 'Zulu']);
        assignRolePermissions($actor, Permission::ViewUsers);
        User::factory()->count(11)->create();
        $first = User::factory()->create(['name' => '000 First']);

        actingAs($actor)->get(route('master-data.users.index', ['sort' => 'name', 'per_page' => 10]))->assertInertia(fn (Assert $page): Assert => $page
            ->has('users.data', 10)->where('users.data.0.id', $first->id)->where('users.total', 13));
        get(route('master-data.users.index', ['sort' => 'name', 'per_page' => 10, 'page' => 2]))->assertInertia(fn (Assert $page): Assert => $page->has('users.data', 3));
        get(route('master-data.users.index', ['per_page' => 999, 'filter' => ['search' => '  ']]))->assertInertia(fn (Assert $page): Assert => $page->where('users.per_page', 10));
        get(route('master-data.users.index', ['per_page' => 25]))->assertInertia(fn (Assert $page): Assert => $page->where('users.per_page', 25));
    });

    it('rejects filters and sorts outside the allowlist', function (array $query): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ViewUsers);
        actingAs($actor)->getJson(route('master-data.users.index', $query))->assertBadRequest();
    })->with([
        [['filter' => ['password' => 'secret']]],
        [['sort' => 'password']],
    ]);

    it('creates a user with a hashed initial password and a single application role', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ManageUsers);
        $role = Role::factory()->create(['name' => 'editor']);
        actingAs($actor)->post(route('master-data.users.store'), [
            'name' => 'Created', 'email' => 'Created@Example.Test', 'role' => $role->name,
            'password' => 'initial-password', 'password_confirmation' => 'initial-password',
            'email_verified_at' => now(), 'status' => 'disabled',
        ])->assertSessionHasNoErrors()->assertRedirect(route('master-data.users.index'));

        $created = User::query()->where('email', 'created@example.test')->sole();
        expect(Hash::check('initial-password', $created->password))->toBeTrue()
            ->and($created->getRoleNames()->all())->toBe(['editor'])
            ->and($created->status)->toBe(UserStatus::Active)
            ->and($created->email_verified_at)->toBeNull()
            ->and($created->invited_at)->toBeNull();
    });

    it('creates a user without assigning a role', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ManageUsers);
        actingAs($actor)->post(route('master-data.users.store'), [
            'name' => 'Unassigned', 'email' => 'unassigned@example.test',
            'password' => 'initial-password', 'password_confirmation' => 'initial-password',
        ])->assertSessionHasNoErrors()->assertRedirect();
        expect(User::query()->where('email', 'unassigned@example.test')->sole()->getRoleNames())->toBeEmpty();
    });

    it('requires a valid confirmed initial password', function (array $data): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ManageUsers);
        actingAs($actor)->post(route('master-data.users.store'), [
            'name' => 'Rejected', 'email' => 'rejected@example.test', ...$data,
        ])->assertSessionHasErrors('password');
        assertDatabaseMissing('users', ['email' => 'rejected@example.test']);
    })->with([
        [[]],
        [['password' => 'short', 'password_confirmation' => 'short']],
        [['password' => 'initial-password', 'password_confirmation' => 'different']],
    ]);

    it('rejects privileged missing and wrong guard roles on create and update', function (string $role): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ManageUsers);
        $target = User::factory()->create();
        Role::factory()->create(['name' => 'api-editor', 'guard_name' => 'api']);
        actingAs($actor)->post(route('master-data.users.store'), [
            'name' => 'Rejected', 'email' => 'rejected@example.test', 'role' => $role,
            'password' => 'initial-password', 'password_confirmation' => 'initial-password',
        ])->assertSessionHasErrors('role');
        patch(route('master-data.users.update', $target), [
            'name' => 'Rejected', 'email' => $target->email, 'role' => $role, 'status' => 'active',
        ])->assertSessionHasErrors('role');
        expect($target->refresh()->name)->not->toBe('Rejected');
    })->with(['superadmin', 'missing', 'api-editor']);

    it('updates users and removes the old verification and reset token when email changes', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ManageUsers);
        $target = User::factory()->create();
        $oldEmail = $target->email;
        Password::broker()->createToken($target);
        $role = Role::factory()->create(['name' => 'editor']);
        actingAs($actor)->patch(route('master-data.users.update', $target), [
            'name' => 'Changed', 'email' => 'Changed@Example.Test', 'role' => $role->name, 'status' => 'disabled',
            'password' => 'replacement-password',
        ])->assertSessionHasNoErrors()->assertRedirect(route('master-data.users.index'));
        expect($target->refresh()->email)->toBe('changed@example.test')
            ->and($target->email_verified_at)->toBeNull()
            ->and($target->status)->toBe(UserStatus::Disabled)
            ->and($target->getRoleNames()->all())->toBe(['editor'])
            ->and(Hash::check('password', $target->password))->toBeTrue();
        assertDatabaseMissing('password_reset_tokens', ['email' => $oldEmail]);

        patch(route('master-data.users.update', $target), [
            'name' => 'Changed again', 'email' => $target->email, 'status' => 'active', 'role' => null,
        ])->assertSessionHasNoErrors();
        expect($target->refresh()->getRoleNames())->toBeEmpty();
    });

    it('rejects duplicate normalized emails and invalid statuses', function (): void {
        $actor = User::factory()->create(['email' => 'existing@example.test']);
        assignRolePermissions($actor, Permission::ManageUsers);
        $target = User::factory()->create();
        actingAs($actor)->post(route('master-data.users.store'), [
            'name' => 'Duplicate', 'email' => 'Existing@Example.Test',
            'password' => 'initial-password', 'password_confirmation' => 'initial-password',
        ])->assertSessionHasErrors('email');
        patch(route('master-data.users.update', $target), [
            'name' => $target->name, 'email' => 'Existing@Example.Test', 'status' => 'invalid',
        ])->assertSessionHasErrors(['email', 'status']);
        patch(route('master-data.users.status', $target), ['status' => 'invalid'])->assertSessionHasErrors('status');
    });

    it('validates precognitive submissions without creating users', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ManageUsers);
        actingAs($actor)->postJson(route('master-data.users.store'), [
            'name' => 'Preview', 'email' => 'preview@example.test',
            'password' => 'initial-password', 'password_confirmation' => 'initial-password',
        ], ['Precognition' => 'true'])->assertNoContent();
        assertDatabaseMissing('users', ['email' => 'preview@example.test']);
    });

    it('protects superadmin accounts even from another superadmin', function (): void {
        $role = Role::findOrCreate(SystemRole::SuperAdmin->value, 'web');
        $actor = User::factory()->create();
        $actor->syncRoles($role);
        $target = User::factory()->create();
        $target->syncRoles($role);
        actingAs($actor)->patch(route('master-data.users.update', $target), [
            'name' => 'Changed', 'email' => $target->email, 'status' => 'disabled',
        ])->assertForbidden();
        patch(route('master-data.users.status', $target), ['status' => 'disabled'])->assertForbidden();
        expect($target->refresh()->status)->toBe(UserStatus::Active);
    });

    it('prevents self deactivation through either endpoint', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ManageUsers);
        actingAs($actor)->patch(route('master-data.users.status', $actor), ['status' => 'disabled'])->assertForbidden();
        patch(route('master-data.users.update', $actor), [
            'name' => $actor->name, 'email' => $actor->email, 'status' => 'disabled',
        ])->assertForbidden();
    });

    it('activates and disables ordinary users', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ManageUsers);
        $target = User::factory()->create();
        actingAs($actor)->patch(route('master-data.users.status', $target), ['status' => 'disabled'])->assertRedirect();
        assertDatabaseHas('users', ['id' => $target->id, 'status' => 'disabled']);
        patch(route('master-data.users.status', $target), ['status' => 'active'])->assertRedirect();
        assertDatabaseHas('users', ['id' => $target->id, 'status' => 'active']);
    });

    it('denies ordinary and read only users permission to mutate accounts', function (bool $canView): void {
        $actor = User::factory()->create();
        if ($canView) {
            assignRolePermissions($actor, Permission::ViewUsers);
        }
        $target = User::factory()->create();
        actingAs($actor);
        get(route('master-data.users.index'))->assertStatus($canView ? 200 : 403);
        getJson(route('master-data.users.export'))->assertStatus($canView ? 200 : 403);
        post(route('master-data.users.store'), [])->assertForbidden();
        patch(route('master-data.users.update', $target), [])->assertForbidden();
        patch(route('master-data.users.status', $target), ['status' => 'disabled'])->assertForbidden();
    })->with([false, true]);

    it('requires authentication and valid UUID route bindings', function (): void {
        get(route('master-data.users.index'))->assertRedirect(route('login'));
        post(route('master-data.users.store'), [])->assertRedirect(route('login'));
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ManageUsers);
        actingAs($actor)->patch(route('master-data.users.update', 'invalid'), [])->assertNotFound();
        patch(route('master-data.users.update', '00000000-0000-4000-8000-000000000000'), [])->assertNotFound();
    });

    it('rejects exports exceeding the limit', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ViewUsers);
        User::factory()->count(1000)->create();
        actingAs($actor)->getJson(route('master-data.users.export'))->assertUnprocessable()->assertJsonValidationErrors('export');
    });

    it('eager loads resource relationships without a query per row', function (): void {
        $actor = User::factory()->create();
        assignRolePermissions($actor, Permission::ViewUsers);
        User::factory()->count(25)->create();
        actingAs($actor);
        get(route('master-data.users.index', ['per_page' => 10]))->assertOk();
        DB::enableQueryLog();
        getJson(route('master-data.users.export', ['filter' => ['search' => 'no matching records']]))->assertOk();
        $emptyCount = count(DB::getQueryLog());
        DB::flushQueryLog();
        getJson(route('master-data.users.export'))->assertOk();
        $fullCount = count(DB::getQueryLog());
        DB::disableQueryLog();
        expect($fullCount - $emptyCount)->toBeLessThanOrEqual(3);
    });
});
