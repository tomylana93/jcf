<?php

declare(strict_types=1);

namespace App\Actions\Api;

use Laravel\Sanctum\Contracts\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

final readonly class RevokeCurrentToken
{
    /**
     * Revoke the token that authenticated the current request, logging that device out.
     */
    public function handle(?HasApiTokens $owner): void
    {
        $token = $owner?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
