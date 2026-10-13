<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\delete;
use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

describe('Profile settings', function () {
    it('renders the profile page', function () {
        $user = User::factory()->create();

        actingAs($user);

        $response = get(route('profile.edit'));

        $response->assertOk();
    });

    it('updates profile information and clears email verification', function () {
        $user = User::factory()->create();

        actingAs($user);

        $response = patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        expect($user->name)->toBe('Test User')
            ->and($user->email)->toBe('test@example.com')
            ->and($user->email_verified_at)->toBeNull();
    });

    it('preserves verification when the email address is unchanged', function () {
        $user = User::factory()->create();

        actingAs($user);

        $response = patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        expect($user->refresh()->email_verified_at)->not->toBeNull();
    });

    it('disables the account while retaining its data', function () {
        $user = User::factory()->create();

        actingAs($user);

        $response = delete(route('profile.disable'), [
            'password' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('home'));

        assertGuest();
        expect($user->refresh()->status)->toBe(UserStatus::Disabled)
            ->and($user->refresh()->exists)->toBeTrue();
    });

    it('forbids superadministrators from disabling their account directly', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin);

        actingAs($user)->delete(route('profile.disable'), ['password' => 'password'])->assertForbidden();

        expect($user->refresh()->status)->toBe(UserStatus::Active);
    });

    it('keeps the account active when the password is incorrect', function () {
        $user = User::factory()->create();

        actingAs($user);
        from(route('profile.edit'));

        $response = delete(route('profile.disable'), [
            'password' => 'wrong-password',
        ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect(route('profile.edit'));

        expect($user->refresh()->status)->toBe(UserStatus::Active);
    });
});
