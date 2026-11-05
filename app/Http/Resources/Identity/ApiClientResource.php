<?php

declare(strict_types=1);

namespace App\Http\Resources\Identity;

use App\Models\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ApiClient */
final class ApiClientResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => $this->status->label(),
            'status_variant' => $this->status->badgeVariant(),
            'tokens_count' => (int) ($this->tokens_count ?? $this->tokens()->count()),
            'created_at' => $this->created_at,
        ];
    }
}
