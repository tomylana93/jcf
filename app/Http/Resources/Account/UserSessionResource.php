<?php

declare(strict_types=1);

namespace App\Http\Resources\Account;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes an active session described by GetUserSessions without exposing its id or payload.
 *
 * @property array{user_agent: string|null, ip_address: string|null, last_active_at: CarbonImmutable, current: bool} $resource
 */
final class UserSessionResource extends JsonResource
{
    /** @return array{device: string, ip_address: string|null, last_active_at: string, current: bool} */
    public function toArray(Request $request): array
    {
        return [
            'device' => $this->resource['user_agent'] ?: __('account.sessions.label.unknown'),
            'ip_address' => $this->resource['ip_address'],
            'last_active_at' => $this->resource['last_active_at']->toIso8601String(),
            'current' => $this->resource['current'],
        ];
    }
}
