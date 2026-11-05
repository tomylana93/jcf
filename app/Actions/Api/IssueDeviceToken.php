<?php

declare(strict_types=1);

namespace App\Actions\Api;

use App\Enums\ApiTokenAbility;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class IssueDeviceToken
{
    /**
     * Issue a mobile token for the device, replacing an earlier token with the same device name.
     *
     * @return array{token: string, token_type: string, abilities: list<string>, email_verified: bool}
     */
    public function handle(User $user, string $deviceName): array
    {
        $abilities = array_map(fn (ApiTokenAbility $ability): string => $ability->value, ApiTokenAbility::cases());

        $plainTextToken = DB::transaction(function () use ($user, $deviceName, $abilities): string {
            $user->tokens()->where('name', $deviceName)->delete();

            return $user->createToken($deviceName, $abilities)->plainTextToken;
        });

        return [
            'token' => $plainTextToken,
            'token_type' => 'Bearer',
            'abilities' => $abilities,
            'email_verified' => $user->hasVerifiedEmail(),
        ];
    }
}
