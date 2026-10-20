<?php

declare(strict_types=1);

namespace App\Http\Resources\Chat;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class ChatUserResource extends JsonResource
{
    /** @return array{id: string, name: string, avatar: string|null} */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'avatar' => $this->avatar];
    }
}
