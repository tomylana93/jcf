<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withSession;

describe('Application entry', function (): void {
    it('redirects guests to login and preserves their intended destination', function (): void {
        withSession(['url.intended' => route('profile.edit')]);

        get(route('home'))->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', route('profile.edit'));
    });

    it('redirects authenticated users to their intended destination', function (): void {
        actingAs(User::factory()->create());
        withSession(['url.intended' => route('profile.edit')]);

        get(route('home'))->assertRedirect(route('profile.edit'))
            ->assertSessionMissing('url.intended');
    });

    it('redirects authenticated users to the dashboard by default', function (): void {
        actingAs(User::factory()->create());

        get(route('home'))->assertRedirect(route('dashboard'));
    });
});
