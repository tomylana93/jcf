<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

describe('Verification notifications', function () {
    beforeEach(function () {
        skipUnlessFortifyHas(Features::emailVerification());
    });

    it('sends verification notification', function () {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        actingAs($user);

        post(route('verification.send'))
            ->assertRedirect(route('home'));

        Notification::assertSentTo($user, VerifyEmail::class);
    });

    it('does not send verification notification if email is verified', function () {
        Notification::fake();

        $user = User::factory()->create();

        actingAs($user);

        post(route('verification.send'))
            ->assertRedirect(route('dashboard', absolute: false));

        Notification::assertNothingSent();
    });
});
