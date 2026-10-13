<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\User;
use Laravel\Fortify\Features;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

describe('Registration', function (): void {
    beforeEach(function (): void {
        skipUnlessFortifyHas(Features::registration());
    });

    it('renders the registration screen', function (): void {
        $response = get(route('register'));

        $response->assertOk();
    });

    it('registers and authenticates new users', function (): void {
        $response = post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',

        ]);

        assertAuthenticated();

        $response->assertRedirect(route('dashboard', absolute: false));
    });
});

describe('Registration foundation', function (): void {
    it('creates an active UUID user without accepting privileged input', function (): void {
        skipUnlessFortifyHas(Features::registration());

        $response = post(route('register.store'), [
            'name' => 'New User', 'email' => 'new@example.com',
            'password' => 'password', 'password_confirmation' => 'password',
            'status' => 'disabled', 'roles' => ['superadmin'],

        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();

        assertAuthenticatedAs($user);
        expect($user->id)->toBeUuid()
            ->and($user->status)->toBe(UserStatus::Active)
            ->and($user->roles)->toBeEmpty();
    });
});
