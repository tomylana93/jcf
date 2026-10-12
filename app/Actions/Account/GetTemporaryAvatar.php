<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class GetTemporaryAvatar
{
    public function handle(User $user, string $uuid): Media
    {
        return $user->media()->where('collection_name', 'avatar_pending')
            ->where('uuid', $uuid)->where('created_at', '>', now()->subDay())->firstOrFail();
    }
}
