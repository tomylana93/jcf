<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

describe('Two-factor challenge', function () {
    beforeEach(function () {
        skipUnlessFortifyHas(Features::twoFactorAuthentication());
    });

    it('redirects users without a pending challenge to login', function () {
        $response = get(route('two-factor.login'));

        $response->assertRedirect(route('login'));
    });

    it('renders the pending two-factor challenge', function () {
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->withTwoFactor()->create();

        post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        get(route('two-factor.login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/TwoFactorChallenge'),
            );
    });
});
