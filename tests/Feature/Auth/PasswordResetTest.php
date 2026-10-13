<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

describe('Password reset', function (): void {
    beforeEach(function (): void {
        skipUnlessFortifyHas(Features::resetPasswords());
    });

    it('renders the password reset link screen', function (): void {
        $response = get(route('password.request'));

        $response->assertOk();
    });

    it('sends a password reset link', function (): void {
        Notification::fake();

        $user = User::factory()->create();

        post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    });

    it('renders the password reset screen', function (): void {
        Notification::fake();

        $user = User::factory()->create();

        post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = get(route('password.reset', $notification->token));

            $response->assertOk();

            return true;
        });
    });

    it('resets the password with a valid token', function (): void {
        Notification::fake();

        $user = User::factory()->create();

        post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    });

    it('rejects an invalid password reset token', function (): void {
        $user = User::factory()->create();

        $response = post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',

        ]);

        $response->assertSessionHasErrors('email');
    });
});
