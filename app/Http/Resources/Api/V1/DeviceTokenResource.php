<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read array{token: string, token_type: string, abilities: list<string>, email_verified: bool} $resource
 */
final class DeviceTokenResource extends JsonResource
{
    /** @return array{token: string, token_type: string, abilities: list<string>, email_verified: bool} */
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
