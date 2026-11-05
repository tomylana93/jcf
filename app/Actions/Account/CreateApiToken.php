<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Enums\ApiTokenAbility;
use App\Enums\ApiTokenExpiration;
use App\Models\ApiClient;
use App\Models\User;

final class CreateApiToken
{
    /**
     * Issue a personal access token and return its plain-text value, which is only available once.
     *
     * @param  list<ApiTokenAbility>  $abilities
     */
    public function handle(User|ApiClient $owner, string $name, array $abilities, ApiTokenExpiration $expiration): string
    {
        return $owner->createToken(
            $name,
            array_values(array_unique(array_map(fn (ApiTokenAbility $ability): string => $ability->value, $abilities))),
            now()->addDays($expiration->days()),
        )->plainTextToken;
    }
}
