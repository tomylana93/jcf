<?php

declare(strict_types=1);

use App\Actions\Account\CreateApiToken;
use App\Actions\Account\DisableAccount;
use App\Actions\Account\UpdateProfile;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Identity\User\UpdateUserStatus;
use App\Enums\ApiTokenAbility;
use App\Enums\ApiTokenExpiration;
use App\Enums\UserStatus;
use App\Models\User;
use App\Settings\ApiSettings;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\PersonalAccessToken;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\freezeTime;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

function issueApiToken(User $user, ApiTokenAbility ...$abilities): string
{
    return resolve(CreateApiToken::class)->handle($user, 'Script', $abilities ?: [ApiTokenAbility::Read], ApiTokenExpiration::OneYear);
}

describe('API token pages', function (): void {
    it('redirects guests to login', function (): void {
        get(route('api-tokens.index'))->assertRedirect(route('login'));
        post(route('api-tokens.store'))->assertRedirect(route('login'));
        delete(route('api-tokens.destroy', 1))->assertRedirect(route('login'));
    });

    it('requires password confirmation', function (): void {
        actingAs(User::factory()->create());

        get(route('api-tokens.index'))->assertRedirect(route('password.confirm'));
        post(route('api-tokens.store'), ['name' => 'Script', 'abilities' => ['read'], 'expiration' => '30'])->assertRedirect(route('password.confirm'));
    });

    it('returns 404 when the API module is switched off', function (): void {
        resolve(ApiSettings::class)->fill(['enabled' => false])->save();
        actingAsWithConfirmedPassword(User::factory()->create());

        get(route('api-tokens.index'))->assertNotFound();
        post(route('api-tokens.store'), ['name' => 'Script', 'abilities' => ['read'], 'expiration' => '30'])->assertNotFound();
    });

    it('lists only owned tokens without exposing token hashes', function (): void {
        $user = User::factory()->create();
        issueApiToken($user, ApiTokenAbility::Read, ApiTokenAbility::Write);
        issueApiToken(User::factory()->create());
        actingAsWithConfirmedPassword($user);

        get(route('api-tokens.index'))->assertInertia(fn (Assert $page) => $page
            ->component('account/ApiTokens')
            ->where('features.api', ['available' => true, 'enabled' => true])
            ->has('tokens', 1)
            ->where('tokens.0.name', 'Script')
            ->where('tokens.0.abilities', ['read', 'write'])
            ->where('tokens.0.last_used_at', null)
            ->where('tokens.0.expired', false)
            ->missing('tokens.0.token')
            ->has('abilityOptions', 2)
            ->has('expirationOptions', 3));
    });
});

describe('Creating API tokens', function (): void {
    it('creates a token with the chosen abilities and expiration and flashes it once', function (): void {
        freezeTime();
        $user = User::factory()->create();
        actingAsWithConfirmedPassword($user);

        post(route('api-tokens.store'), ['name' => 'Reporting', 'abilities' => ['write'], 'expiration' => '90'])
            ->assertRedirect(route('api-tokens.index'))
            ->assertInertiaFlash('apiToken')
            ->assertInertiaFlash('toast.message', __('account.api_tokens.status.created'));

        $token = $user->tokens()->sole();
        expect($token->name)->toBe('Reporting')
            ->and($token->abilities)->toBe(['write'])
            ->and($token->expires_at?->toIso8601String())->toBe(now()->addDays(90)->toIso8601String());
    });

    it('rejects an empty payload', function (): void {
        actingAsWithConfirmedPassword(User::factory()->create());

        post(route('api-tokens.store'), [])->assertSessionHasErrors([
            'name' => __('validation.required', ['attribute' => __('validation.attributes.token_name')]),
            'abilities' => __('validation.required', ['attribute' => __('validation.attributes.abilities')]),
            'expiration' => __('validation.required', ['attribute' => __('validation.attributes.expiration')]),
        ]);
    });

    it('rejects unknown abilities and expirations', function (array $payload, string $field): void {
        actingAsWithConfirmedPassword(User::factory()->create());

        post(route('api-tokens.store'), [...['name' => 'Script', 'abilities' => ['read'], 'expiration' => '30'], ...$payload])
            ->assertSessionHasErrors($field);
    })->with([
        'ability' => [['abilities' => ['admin']], 'abilities.0'],
        'expiration' => [['expiration' => '3650'], 'expiration'],
        'long name' => [['name' => str_repeat('a', 101)], 'name'],
    ]);
});

describe('Revoking API tokens', function (): void {
    it('revokes an owned token', function (): void {
        $user = User::factory()->create();
        issueApiToken($user);
        $token = $user->tokens()->sole();
        actingAsWithConfirmedPassword($user);

        delete(route('api-tokens.destroy', $token->id))
            ->assertRedirect(route('api-tokens.index'))
            ->assertInertiaFlash('toast.message', __('account.api_tokens.status.revoked'));

        assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
    });

    it('returns 404 for a token owned by another user', function (): void {
        $other = User::factory()->create();
        issueApiToken($other);
        $token = $other->tokens()->sole();
        actingAsWithConfirmedPassword(User::factory()->create());

        delete(route('api-tokens.destroy', $token->id))->assertNotFound();

        assertDatabaseHas('personal_access_tokens', ['id' => $token->id]);
    });
});

describe('Using API tokens', function (): void {
    it('authenticates API requests with an issued read token', function (): void {
        $user = User::factory()->create();

        getJson(route('api.v1.user'), ['Authorization' => 'Bearer '.issueApiToken($user)])
            ->assertOk()->assertJsonPath('data.id', $user->id);
    });

    it('returns 403 when the token lacks the read ability', function (): void {
        getJson(route('api.v1.user'), ['Authorization' => 'Bearer '.issueApiToken(User::factory()->create(), ApiTokenAbility::Write)])
            ->assertForbidden();
    });

    it('returns 401 for an expired token', function (): void {
        $plainTextToken = issueApiToken(User::factory()->create());
        PersonalAccessToken::query()->update(['expires_at' => now()->subMinute()]);

        getJson(route('api.v1.user'), ['Authorization' => 'Bearer '.$plainTextToken])->assertUnauthorized();
    });
});

describe('Automatic token revocation', function (): void {
    it('revokes all tokens when the account is disabled', function (string $path): void {
        $user = User::factory()->create();
        issueApiToken($user);
        issueApiToken($user);

        match ($path) {
            'self' => resolve(DisableAccount::class)->handle($user),
            'status' => resolve(UpdateUserStatus::class)->handle(null, $user, UserStatus::Disabled),
        };

        expect($user->tokens()->count())->toBe(0);
    })->with(['self', 'status']);

    it('revokes all tokens when the password changes', function (string $path): void {
        $user = User::factory()->create();
        issueApiToken($user);

        if ($path === 'update') {
            actingAs($user);
            put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertSessionHasNoErrors();
        } else {
            resolve(ResetUserPassword::class)->reset($user, ['password' => 'new-password', 'password_confirmation' => 'new-password']);
        }

        expect($user->tokens()->count())->toBe(0);
    })->with(['update', 'reset']);

    it('revokes all tokens when the user is deleted', function (): void {
        $user = User::factory()->create();
        issueApiToken($user);

        $user->delete();

        expect(PersonalAccessToken::query()->count())->toBe(0);
    });

    it('keeps tokens when unrelated profile fields change', function (): void {
        $user = User::factory()->create();
        issueApiToken($user);

        resolve(UpdateProfile::class)->handle($user, ['name' => 'Renamed', 'email' => $user->email]);

        expect($user->tokens()->count())->toBe(1);
    });
});
