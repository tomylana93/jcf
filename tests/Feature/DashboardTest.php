<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('dashboard access', function () {
    it('redirects guests to login', function () {
        $response = get(route('dashboard'));
        $response->assertRedirect(route('login'));
    });

    it('renders the dashboard for authenticated users', function () {
        $user = User::factory()->create();
        actingAs($user);

        $response = get(route('dashboard'));
        $response->assertOk();
    });
});
