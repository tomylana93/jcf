<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\ApiClient;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

final class GetApiTokens
{
    /** @return list<array{id: int, name: string, abilities: list<string>, last_used_at: string|null, expires_at: string|null, created_at: string, expired: bool}> */
    public function handle(User|ApiClient $owner): array
    {
        return array_values($owner->tokens()->latest()->latest('id')->get()
            ->map(fn (PersonalAccessToken $token): array => [
                'id' => (int) $token->getKey(),
                'name' => (string) $token->name,
                'abilities' => array_values(array_map(strval(...), $token->abilities ?? [])),
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'expires_at' => $token->expires_at?->toIso8601String(),
                'created_at' => (string) $token->created_at?->toIso8601String(),
                'expired' => $token->expires_at?->isPast() ?? false,
            ])->all());
    }
}
