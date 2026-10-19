<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Models\User;
use App\Models\WebsiteContent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class UploadWebsiteImage
{
    public function handle(User $actor, WebsiteContent $content, UploadedFile $image): Media
    {
        Gate::forUser($actor)->authorize('update', $content);
        Validator::make(['image' => $image], ['image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000']])->validate();

        return $content->addMedia($image)->usingFileName(Str::uuid().'.'.$image->guessExtension())->toMediaCollection('website-images');
    }
}
