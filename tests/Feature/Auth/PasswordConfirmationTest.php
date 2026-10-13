<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('Password confirmation', function (): void {
    it('renders the password confirmation screen', function (): void {
        $user = User::factory()->create();

        actingAs($user);

        $response = get(route('password.confirm'));

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('auth/ConfirmPassword'),
        );
    });

    it('redirects guests to login before password confirmation', function (): void {
        $response = get(route('password.confirm'));

        $response->assertRedirect(route('login'));
    });
});
