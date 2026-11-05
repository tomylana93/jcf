<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class PruneExpiredAvatarUploads
{
    /**
     * Delete temporary avatar uploads older than a day; saved avatars and recent drafts are kept.
     */
    public function handle(): void
    {
        Media::query()->where('model_type', (new User)->getMorphClass())
            ->whereIn('collection_name', ['avatar_pending', 'avatar_prepared'])
            ->where('created_at', '<=', now()->subDay())
            ->eachById(fn (Media $media) => $media->delete());
    }
}
