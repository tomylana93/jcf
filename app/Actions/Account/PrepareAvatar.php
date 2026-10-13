<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

final class PrepareAvatar
{
    public function handle(User $user, UploadedFile|string $file, string $collection, string $errorField = 'avatar'): Media
    {
        $fileName = Str::uuid().'.'.($file instanceof UploadedFile ? $file->guessExtension() : pathinfo($file, PATHINFO_EXTENSION));

        try {
            return $user->addMedia($file)->preservingOriginal()
                ->usingName('Avatar')->usingFileName($fileName)
                ->toMediaCollection($collection);
        } catch (Throwable $exception) {
            $user->media()->where('collection_name', $collection)->where('file_name', $fileName)
                ->get()->each(fn (Media $media) => $media->delete());

            report($exception);

            throw ValidationException::withMessages([$errorField => __('account.avatar.status.failed')]);
        }
    }
}
