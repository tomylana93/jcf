<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\User;
use Laravel\Fortify\Features;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\post;

describe('registration foundation', function (): void {
    it('creates an active UUID user without accepting privileged input', function (): void {
        skipUnlessFortifyHas(Features::registration());

        post(route('register.store'), [
            'name' => 'New User', 'email' => 'new@example.com',
            'password' => 'password', 'password_confirmation' => 'password',
            'status' => 'disabled', 'roles' => ['superadmin'],
        ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();
        assertAuthenticatedAs($user);
        expect($user->id)->toBeUuid()
            ->and($user->status)->toBe(UserStatus::Active)
            ->and($user->roles)->toBeEmpty();
    });
});
