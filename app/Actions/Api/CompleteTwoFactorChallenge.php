<?php

declare(strict_types=1);

namespace App\Actions\Api;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Laravel\Fortify\Fortify;

final readonly class CompleteTwoFactorChallenge
{
    public const int MAX_ATTEMPTS = 5;

    public function __construct(private TwoFactorAuthenticationProvider $provider) {}

    /**
     * Verify a TOTP or recovery code for a pending challenge and return the user and device name.
     *
     * @return array{user: User, device_name: string}
     *
     * @throws ValidationException
     */
    public function handle(string $challenge, ?string $code, ?string $recoveryCode): array
    {
        $key = StartTwoFactorChallenge::cacheKey($challenge);
        /** @var array{user_id: string, device_name: string, attempts: int}|null $pending */
        $pending = Cache::get($key);
        $user = $pending === null ? null : User::query()->find($pending['user_id']);

        if ($pending === null || $user === null || $user->status === UserStatus::Disabled || ! $user->hasEnabledTwoFactorAuthentication()) {
            Cache::forget($key);

            throw ValidationException::withMessages(['challenge' => __('auth.two_factor_expired')]);
        }

        if ($recoveryCode !== null && $recoveryCode !== '') {
            $valid = collect($user->recoveryCodes())->first(fn (string $stored): bool => hash_equals($stored, $recoveryCode));
            if (is_string($valid)) {
                $user->replaceRecoveryCode($valid);
                event(new RecoveryCodeReplaced($user, $valid));
            }
        } else {
            $valid = $code !== null && $code !== '' && $this->provider->verify(Fortify::currentEncrypter()->decrypt($user->two_factor_secret), $code);
        }

        if ($valid === false || $valid === null) {
            event(new TwoFactorAuthenticationFailed($user));
            $pending['attempts']++;
            $pending['attempts'] >= self::MAX_ATTEMPTS
                ? Cache::forget($key)
                : Cache::put($key, $pending, StartTwoFactorChallenge::TTL_SECONDS);

            throw ValidationException::withMessages([$recoveryCode ? 'recovery_code' : 'code' => __('auth.two_factor_failed')]);
        }

        Cache::forget($key);
        event(new ValidTwoFactorAuthenticationCodeProvided($user));

        return ['user' => $user, 'device_name' => $pending['device_name']];
    }
}
