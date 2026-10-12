<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final readonly class UploadTemporaryAvatar
{
    public function __construct(private PrepareAvatar $prepareAvatar) {}

    public function handle(User $user, UploadedFile $file): Media
    {
        return $this->prepareAvatar->handle($user, $file, 'avatar_pending');
    }
}
