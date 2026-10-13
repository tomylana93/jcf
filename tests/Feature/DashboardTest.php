<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('Dashboard access', function (): void {
    it('redirects guests to login', function (): void {
        $response = get(route('dashboard'));

        $response->assertRedirect(route('login'));
    });

    it('renders the dashboard for authenticated users', function (): void {
        $user = User::factory()->create();
        actingAs($user);

        $response = get(route('dashboard'));

        $response->assertOk();
    });
});
