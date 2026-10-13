<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseCount;

beforeEach(function (): void {
    config(['superadmin.name' => 'System Admin', 'superadmin.email' => 'ADMIN@example.com', 'superadmin.password' => 'Strong-password-123!']);
});

describe('Superadmin initialization', function (): void {
    it('creates a verified active administrator with a normalized email and hashed password', function (): void {
        artisan('init:permission')->assertSuccessful();
        artisan('init:superadmin')->expectsOutput('System administrator created.')->assertSuccessful();

        $user = User::query()->sole();

        expect($user->email)->toBe('admin@example.com')
            ->and($user->name)->toBe('System Admin')
            ->and($user->email_verified_at)->not->toBeNull()
            ->and($user->hasRole(Role::SuperAdmin))->toBeTrue()
            ->and($user->status)->toBe(UserStatus::Active)
            ->and(Hash::check('Strong-password-123!', $user->password))->toBeTrue();
    });

    it('preserves the existing administrator account on subsequent runs', function (): void {
        $user = User::factory()->unverified()->create(['email' => 'admin@example.com']);
        $user->syncRoles(Role::SuperAdmin);
        $original = $user->fresh()->getRawOriginal();

        artisan('init:superadmin')->expectsOutput('System administrator already exists.')->assertSuccessful();

        assertDatabaseCount('users', 1);
        expect($user->fresh()->getRawOriginal())->toBe($original);
    });

    it('refuses to promote or change an existing ordinary user', function (): void {
        $user = User::factory()->create(['email' => 'admin@example.com']);
        $original = $user->fresh()->getRawOriginal();

        artisan('init:superadmin')
            ->expectsOutput('The configured email belongs to an existing user without the SuperAdmin role.')
            ->assertFailed();

        expect($user->fresh()->getRawOriginal())->toBe($original)
            ->and($user->fresh()->roles)->toBeEmpty();
    });

    it('rejects invalid configuration without creating an account', function (string $key, mixed $value): void {
        config(['superadmin.'.$key => $value]);

        artisan('init:superadmin')->assertFailed();

        assertDatabaseCount('users', 0);
    })->with([
        'missing name' => ['name', null],
        'invalid name type' => ['name', []],
        'long name' => ['name', str_repeat('x', 256)],
        'missing email' => ['email', null],
        'invalid email' => ['email', 'invalid'],
        'long email' => ['email', str_repeat('x', 256).'@example.com'],
        'missing password' => ['password', null],
        'invalid password type' => ['password', []],
        'short password' => ['password', 'short'],
    ]);
});
