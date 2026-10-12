<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Signature('avatars:prune')]
#[Description('Delete expired temporary avatar uploads')]
final class PruneTemporaryAvatars extends Command
{
    public function handle(): int
    {
        Media::query()->where('model_type', (new User)->getMorphClass())
            ->whereIn('collection_name', ['avatar_pending', 'avatar_prepared'])
            ->where('created_at', '<=', now()->subDay())
            ->eachById(fn (Media $media) => $media->delete());

        return self::SUCCESS;
    }
}
