<?php

declare(strict_types=1);

use App\Actions\Api\CompleteTwoFactorChallenge;
use App\Enums\UserStatus;
use App\Models\ApiClient;
use App\Models\User;
use App\Settings\AuthSettings;
use Illuminate\Support\Facades\Event;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Sanctum\PersonalAccessToken;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\mock;
use function Pest\Laravel\postJson;
use function Pest\Laravel\travel;

/** @return array{email: string, password: string, device_name: string} */
function mobileLogin(User $user, string $device = 'Pixel 9'): array
{
    return ['email' => $user->email, 'password' => 'password', 'device_name' => $device];
}

function acceptTwoFactorCode(bool $valid): void
{
    mock(TwoFactorAuthenticationProvider::class)->shouldReceive('verify')->andReturn($valid);
}

function startChallenge(User $user): string
{
    return postJson(route('api.v1.auth.token.store'), mobileLogin($user))->assertOk()->json('data.challenge');
}

describe('Mobile login', function (): void {
    it('issues a read and write device token that authenticates API requests', function (): void {
        $user = User::factory()->create();

        $response = postJson(route('api.v1.auth.token.store'), mobileLogin($user))
            ->assertCreated()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.abilities', ['read', 'write'])
            ->assertJsonPath('data.email_verified', true);

        expect($user->tokens()->sole()->name)->toBe('Pixel 9');
        getJson(route('api.v1.user'), ['Authorization' => 'Bearer '.$response->json('data.token')])
            ->assertOk()->assertJsonPath('data.id', $user->id);
    });

    it('matches the email case-insensitively like the web login', function (): void {
        $user = User::factory()->create(['email' => 'mobile@example.com']);

        postJson(route('api.v1.auth.token.store'), [...mobileLogin($user), 'email' => 'MOBILE@example.com'])->assertCreated();
    });

    it('rejects wrong credentials with one generic message', function (string $case): void {
        $user = User::factory()->create();
        $payload = $case === 'password' ? [...mobileLogin($user), 'password' => 'wrong'] : [...mobileLogin($user), 'email' => 'missing@example.com'];

        postJson(route('api.v1.auth.token.store'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors(['email' => __('auth.failed')]);
        expect(PersonalAccessToken::query()->count())->toBe(0);
    })->with(['password', 'unknown email']);

    it('rejects a disabled account', function (): void {
        $user = User::factory()->disabled()->create();

        postJson(route('api.v1.auth.token.store'), mobileLogin($user))
            ->assertUnprocessable()->assertJsonValidationErrors(['email' => __('auth.disabled')]);
    });

    it('rejects an empty payload', function (): void {
        postJson(route('api.v1.auth.token.store'), [])
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password', 'device_name']);
    });

    it('replaces the token of the same device and keeps other tokens', function (): void {
        $user = User::factory()->create();
        $user->createToken('Reporting script');
        postJson(route('api.v1.auth.token.store'), mobileLogin($user, 'Pixel 9'))->assertCreated();
        postJson(route('api.v1.auth.token.store'), mobileLogin($user, 'iPad'))->assertCreated();

        postJson(route('api.v1.auth.token.store'), mobileLogin($user, 'Pixel 9'))->assertCreated();

        expect($user->tokens()->pluck('name')->sort()->values()->all())->toBe(['Pixel 9', 'Reporting script', 'iPad']);
    });

    it('issues a token for an unverified email that verified endpoints still reject', function (): void {
        resolve(AuthSettings::class)->fill(['email_verification_required' => true])->save();
        $user = User::factory()->unverified()->create();

        $token = postJson(route('api.v1.auth.token.store'), mobileLogin($user))
            ->assertCreated()->assertJsonPath('data.email_verified', false)->json('data.token');

        getJson(route('api.v1.user'), ['Authorization' => 'Bearer '.$token])->assertForbidden();
    });

    it('throttles repeated attempts with the web login limits', function (): void {
        resolve(AuthSettings::class)->fill(['login_max_attempts' => 2, 'login_lockout_minutes' => 1])->save();
        $user = User::factory()->create();
        $payload = [...mobileLogin($user), 'password' => 'wrong'];

        postJson(route('api.v1.auth.token.store'), $payload)->assertUnprocessable();
        postJson(route('api.v1.auth.token.store'), $payload)->assertUnprocessable();
        postJson(route('api.v1.auth.token.store'), mobileLogin($user))->assertTooManyRequests();
    });
});

describe('Mobile two-factor login', function (): void {
    it('returns a challenge instead of a token when 2FA is enabled', function (): void {
        $user = User::factory()->withTwoFactor()->create();

        postJson(route('api.v1.auth.token.store'), mobileLogin($user))
            ->assertOk()
            ->assertJsonPath('data.two_factor', true)
            ->assertJsonPath('data.expires_in', 300)
            ->assertJsonMissingPath('data.token');

        expect($user->tokens()->count())->toBe(0);
    });

    it('issues the device token for a valid code and consumes the challenge', function (): void {
        $user = User::factory()->withTwoFactor()->create();
        $challenge = startChallenge($user);
        acceptTwoFactorCode(true);

        postJson(route('api.v1.auth.two-factor'), ['challenge' => $challenge, 'code' => '123456'])
            ->assertCreated()->assertJsonPath('data.token_type', 'Bearer');

        expect($user->tokens()->sole()->name)->toBe('Pixel 9');
        postJson(route('api.v1.auth.two-factor'), ['challenge' => $challenge, 'code' => '123456'])
            ->assertUnprocessable()->assertJsonValidationErrors(['challenge' => __('auth.two_factor_expired')]);
    });

    it('rejects an invalid code and invalidates the challenge after too many attempts', function (): void {
        $user = User::factory()->withTwoFactor()->create();
        $challenge = startChallenge($user);
        acceptTwoFactorCode(false);

        foreach (range(1, CompleteTwoFactorChallenge::MAX_ATTEMPTS) as $ignored) {
            postJson(route('api.v1.auth.two-factor'), ['challenge' => $challenge, 'code' => '000000'])
                ->assertUnprocessable()->assertJsonValidationErrors(['code' => __('auth.two_factor_failed')]);
        }

        travel(61)->seconds();
        acceptTwoFactorCode(true);
        postJson(route('api.v1.auth.two-factor'), ['challenge' => $challenge, 'code' => '123456'])
            ->assertUnprocessable()->assertJsonValidationErrors(['challenge' => __('auth.two_factor_expired')]);
        expect($user->tokens()->count())->toBe(0);
    });

    it('accepts a recovery code once and replaces it', function (): void {
        Event::fake([RecoveryCodeReplaced::class]);
        $user = User::factory()->withTwoFactor()->create();

        postJson(route('api.v1.auth.two-factor'), ['challenge' => startChallenge($user), 'recovery_code' => 'recovery-code-1'])
            ->assertCreated();

        expect($user->fresh()->recoveryCodes())->not->toContain('recovery-code-1');
        Event::assertDispatched(RecoveryCodeReplaced::class);
        postJson(route('api.v1.auth.two-factor'), ['challenge' => startChallenge($user), 'recovery_code' => 'recovery-code-1'])
            ->assertUnprocessable()->assertJsonValidationErrors(['recovery_code' => __('auth.two_factor_failed')]);
    });

    it('rejects an expired challenge or an account disabled meanwhile', function (string $case): void {
        $user = User::factory()->withTwoFactor()->create();
        $challenge = startChallenge($user);
        acceptTwoFactorCode(true);
        if ($case === 'expired') {
            travel(301)->seconds();
        } else {
            $user->status = UserStatus::Disabled;
            $user->save();
        }

        postJson(route('api.v1.auth.two-factor'), ['challenge' => $challenge, 'code' => '123456'])
            ->assertUnprocessable()->assertJsonValidationErrors(['challenge' => __('auth.two_factor_expired')]);
    })->with(['expired', 'disabled']);

    it('requires a code or a recovery code', function (): void {
        postJson(route('api.v1.auth.two-factor'), ['challenge' => str_repeat('a', 64)])
            ->assertUnprocessable()->assertJsonValidationErrors(['code']);
    });
});

describe('Mobile logout', function (): void {
    it('revokes only the token of the current device', function (): void {
        $user = User::factory()->create();
        $current = $user->createToken('Pixel 9', ['read'])->plainTextToken;
        $user->createToken('iPad', ['read']);

        deleteJson(route('api.v1.auth.token.destroy'), [], ['Authorization' => 'Bearer '.$current])->assertNoContent();

        expect($user->tokens()->pluck('name')->all())->toBe(['iPad']);
    });

    it('returns 403 for a client token', function (): void {
        $token = ApiClient::factory()->create()->createToken('Sync')->plainTextToken;

        deleteJson(route('api.v1.auth.token.destroy'), [], ['Authorization' => 'Bearer '.$token])->assertForbidden();
    });
});
