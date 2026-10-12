<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

describe('authentication', function () {
    it('renders the login screen', function () {
        $response = get(route('login'));

        $response->assertOk();
    });

    it('authenticates users with valid credentials', function () {
        $user = User::factory()->create();

        $response = post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    });

    it('redirects users with two-factor authentication to the challenge', function () {
        skipUnlessFortifyHas(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->withTwoFactor()->create();

        $response = post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $response->assertSessionHas('login.id', $user->id);
        assertGuest();
    });

    it('rejects an invalid password', function () {
        $user = User::factory()->create();

        post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        assertGuest();
    });

    it('logs out authenticated users', function () {
        $user = User::factory()->create();

        actingAs($user);

        $response = post(route('logout'));

        $response->assertRedirect(route('home'));

        assertGuest();
    });

    it('rate limits failed login attempts', function () {
        $user = User::factory()->create();

        RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

        $response = post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertTooManyRequests();
    });
});
