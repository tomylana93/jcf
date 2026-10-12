<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;

final class GetAvatarSettings
{
    /** @return array{current_url: string, temporary: array{id: string, url: string}|null} */
    public function handle(User $user): array
    {
        $temporary = $user->getFirstMedia('avatar_pending');

        return [
            'current_url' => $user->getFirstMediaUrl('avatar', 'preview'),
            'temporary' => $temporary !== null && $temporary->created_at->gt(now()->subDay())
                ? ['id' => $temporary->uuid, 'url' => route('profile.avatar.preview', $temporary->uuid)]
                : null,
        ];
    }
}
