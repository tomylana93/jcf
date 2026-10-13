<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('Email verification', function (): void {
    beforeEach(function (): void {
        skipUnlessFortifyHas(Features::emailVerification());
    });

    it('renders the email verification screen', function (): void {
        $user = User::factory()->unverified()->create();

        actingAs($user);

        $response = get(route('verification.notice'));

        $response->assertOk();
    });

    it('redirects unverified users to the verification prompt', function (): void {
        $user = User::factory()->unverified()->create();

        actingAs($user);

        $response = get(route('sessions.index'));

        $response->assertRedirect(route('verification.notice'));
    });

    it('verifies email with a valid signed link', function (): void {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        actingAs($user);

        $response = get($verificationUrl);

        Event::assertDispatched(Verified::class);

        expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    });

    it('rejects an invalid email hash', function (): void {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')],
        );

        actingAs($user);

        get($verificationUrl);

        Event::assertNotDispatched(Verified::class);

        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    });

    it('rejects an invalid user identifier', function (): void {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => 123, 'hash' => sha1($user->email)],
        );

        actingAs($user);

        get($verificationUrl);

        Event::assertNotDispatched(Verified::class);

        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    });

    it('redirects verified users from the prompt to the dashboard', function (): void {
        $user = User::factory()->create();

        Event::fake();

        actingAs($user);

        $response = get(route('verification.notice'));

        Event::assertNotDispatched(Verified::class);
        $response->assertRedirect(route('dashboard', absolute: false));
    });

    it('redirects verified users without dispatching another verification event', function (): void {
        $user = User::factory()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        actingAs($user);

        get($verificationUrl)
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        Event::assertNotDispatched(Verified::class);

        expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    });
});
