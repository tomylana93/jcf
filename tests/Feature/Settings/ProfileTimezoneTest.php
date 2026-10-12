<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

describe('profile timezone', function (): void {
    it('saves or clears a valid timezone', function (?string $timezone): void {
        $user = User::factory()->create(['timezone' => 'Europe/London']);

        actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name, 'email' => $user->email, 'timezone' => $timezone,
        ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

        expect($user->refresh()->timezone)->toBe($timezone);
    })->with(['Asia/Jakarta', null]);

    it('rejects an invalid timezone', function (): void {
        $user = User::factory()->create(['timezone' => 'Europe/London']);

        actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name, 'email' => $user->email, 'timezone' => 'invalid',
        ])->assertSessionHasErrors('timezone');

        expect($user->refresh()->timezone)->toBe('Europe/London');
    });

    it('preserves the timezone when omitted and ignores privileged attributes', function (): void {
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);

        actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name, 'email' => $user->email,
            'status' => 'disabled', 'roles' => ['superadmin'],
        ])->assertSessionHasNoErrors();

        expect($user->refresh()->timezone)->toBe('Asia/Jakarta')
            ->and($user->status)->toBe(UserStatus::Active)
            ->and($user->roles)->toBeEmpty();
    });

    it('shares resolved timezone and avatar with the authenticated page', function (): void {
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);

        actingAs($user)->get(route('profile.edit'))->assertInertia(fn (Assert $page): Assert => $page
            ->where('auth.user.id', $user->id)
            ->where('auth.user.avatar', '')
            ->missing('auth.user.media')
            ->where('timezone.current', 'Asia/Jakarta')
            ->where('timezone.source', 'profile'));
    });
});
