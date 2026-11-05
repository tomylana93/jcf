<?php

declare(strict_types=1);

namespace App\Actions\MasterData\ApiClient;

use App\Actions\Audit\RecordAuditLog;
use App\Models\ApiClient;
use App\Models\User;

final readonly class RevokeApiClientToken
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    /**
     * Revoke one of the client's tokens and record it. Tokens of other owners are reported as missing.
     */
    public function handle(ApiClient $client, User $actor, int $tokenId): void
    {
        $token = $client->tokens()->whereKey($tokenId)->firstOrFail();
        $token->delete();

        $this->recordAuditLog->handle($actor, 'api_client.token_revoked', 'api_client', $client->id, $client->name, ['name' => $token->name], []);
    }
}
