<?php

declare(strict_types=1);

namespace App\Actions\Api;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class StartTwoFactorChallenge
{
    public const int TTL_SECONDS = 300;

    public static function cacheKey(string $challenge): string
    {
        return 'api-two-factor-challenge:'.hash('sha256', $challenge);
    }

    /**
     * Store a short-lived, single-use challenge and return its opaque identifier.
     *
     * @return array{two_factor: true, challenge: string, expires_in: int}
     */
    public function handle(User $user, string $deviceName): array
    {
        $challenge = Str::random(64);

        Cache::put(self::cacheKey($challenge), ['user_id' => $user->id, 'device_name' => $deviceName, 'attempts' => 0], self::TTL_SECONDS);

        return ['two_factor' => true, 'challenge' => $challenge, 'expires_in' => self::TTL_SECONDS];
    }
}
