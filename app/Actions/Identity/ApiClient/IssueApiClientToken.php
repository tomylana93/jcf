<?php

declare(strict_types=1);

namespace App\Actions\Identity\ApiClient;

use App\Actions\Account\CreateApiToken;
use App\Actions\Audit\RecordAuditLog;
use App\Enums\ApiTokenAbility;
use App\Enums\ApiTokenExpiration;
use App\Enums\AuditEvent;
use App\Models\ApiClient;
use App\Models\User;

final readonly class IssueApiClientToken
{
    public function __construct(private CreateApiToken $createApiToken, private RecordAuditLog $recordAuditLog) {}

    /**
     * Issue a token for the client, audit it without its secret, and return the plain-text value once.
     *
     * @param  list<ApiTokenAbility>  $abilities
     */
    public function handle(User $actor, ApiClient $client, string $name, array $abilities, ApiTokenExpiration $expiration): string
    {
        $plainTextToken = $this->createApiToken->handle($client, $name, $abilities, $expiration);

        $this->recordAuditLog->handle($actor, AuditEvent::ApiClientTokenIssued, 'api_client', $client->id, $client->name, [], [
            'name' => $name,
            'abilities' => array_map(fn (ApiTokenAbility $ability): string => $ability->value, $abilities),
            'expiration' => $expiration->value,
        ]);

        return $plainTextToken;
    }
}
