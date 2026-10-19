<?php

declare(strict_types=1);

namespace App\Http\Resources\Website;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ContactMessage */
final class ContactMessageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'subject' => $this->subject, 'created_at' => $this->created_at?->toIso8601String(), 'handled_at' => $this->handled_at?->toIso8601String()];
    }
}
