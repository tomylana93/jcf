<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ApiClient */
final class ApiClientResource extends JsonResource
{
    /** @return array{id: string, name: string} */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
