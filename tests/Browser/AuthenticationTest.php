<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;

describe('Browser authentication', function () {
    it('redirects guests from the dashboard to the login form', function (): void {
        visit(route('dashboard', absolute: false))
            ->assertPathIs('/login')
            ->assertSee(__('auth.login.card.title'))
            ->assertNoJavaScriptErrors();

        assertGuest();
    });

    it('signs in with valid credentials and renders the dashboard', function (): void {
        $user = User::factory()->create();

        visit(route('login', absolute: false))
            ->fill('email', $user->email)
            ->fill('password', 'password')
            ->click('@login-button')
            ->assertPathIs('/dashboard')
            ->assertSee('Dashboard')
            ->assertNoJavaScriptErrors();

        assertAuthenticatedAs($user);
    });

    it('displays a validation error for an incorrect password', function (): void {
        $user = User::factory()->create();

        visit(route('login', absolute: false))
            ->fill('email', $user->email)
            ->fill('password', 'incorrect-password')
            ->click('@login-button')
            ->assertSee(__('auth.failed'))
            ->assertPathIs('/login')
            ->assertNoJavaScriptErrors();

        assertGuest();
    });
});
