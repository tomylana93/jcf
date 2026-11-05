<?php

declare(strict_types=1);

namespace App\Actions\MasterData\ApiClient;

use App\Actions\Audit\RecordAuditLog;
use App\Enums\ApiClientStatus;
use App\Models\ApiClient;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class SaveApiClient
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    /**
     * Create a client, or update the given one, and record the change in the audit log.
     *
     * @param  array{name: string, description?: string|null, status: string}  $data
     */
    public function handle(array $data, User $actor, ?ApiClient $client = null): ApiClient
    {
        return DB::transaction(function () use ($data, $actor, $client): ApiClient {
            $creating = $client === null;
            $client ??= new ApiClient;
            $before = $creating ? [] : $this->auditValues($client);

            $client->fill([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => ApiClientStatus::from($data['status']),
            ]);
            if ($creating) {
                $client->created_by = $actor->id;
            }
            $client->save();

            $this->recordAuditLog->handle($actor, $creating ? 'api_client.created' : 'api_client.updated', 'api_client', $client->id, $client->name, $before, $this->auditValues($client));

            return $client;
        });
    }

    /** @return array{name: string, description: string|null, status: string} */
    private function auditValues(ApiClient $client): array
    {
        return ['name' => $client->name, 'description' => $client->description, 'status' => $client->status->value];
    }
}
