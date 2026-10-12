<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\put;
use function Pest\Laravel\withSession;

describe('security settings', function () {
    it('renders enabled passkey and two-factor settings', function () {
        skipUnlessFortifyHas(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);
        Features::passkeys([
            'confirmPassword' => true,
        ]);

        $user = User::factory()->create();

        actingAs($user);
        withSession(['auth.password_confirmed_at' => time()]);

        get(route('security.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('account/Security')
                ->where('canManagePasskeys', true)
                ->where('passkeys', [])
                ->where('canManageTwoFactor', true)
                ->where('twoFactorEnabled', false),
            );
    });

    it('requires password confirmation when enabled', function () {
        skipUnlessFortifyHas(Features::twoFactorAuthentication());

        $user = User::factory()->create();

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        actingAs($user);

        $response = get(route('security.edit'));

        $response->assertRedirect(route('password.confirm'));
    });

    it('renders security settings with authentication features disabled', function () {
        skipUnlessFortifyHas(Features::twoFactorAuthentication());

        config(['fortify.features' => []]);

        $user = User::factory()->create();

        actingAs($user);
        withSession(['auth.password_confirmed_at' => time()]);

        get(route('security.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('account/Security')
                ->where('canManagePasskeys', false)
                ->where('passkeys', [])
                ->where('canManageTwoFactor', false)
                ->missing('twoFactorEnabled')
                ->missing('requiresConfirmation'),
            );
    });

    it('updates the password with the correct current password', function () {
        $user = User::factory()->create();

        actingAs($user);
        from(route('security.edit'));

        $response = put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('security.edit'));

        expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
    });

    it('rejects password updates with an incorrect current password', function () {
        $user = User::factory()->create();

        actingAs($user);
        from(route('security.edit'));

        $response = put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response
            ->assertSessionHasErrors('current_password')
            ->assertRedirect(route('security.edit'));
    });
});
