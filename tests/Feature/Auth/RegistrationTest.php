<?php

declare(strict_types=1);

use Laravel\Fortify\Features;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

describe('registration', function () {
    beforeEach(function () {
        skipUnlessFortifyHas(Features::registration());
    });

    it('renders the registration screen', function () {
        $response = get(route('register'));

        $response->assertOk();
    });

    it('registers and authenticates new users', function () {
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
