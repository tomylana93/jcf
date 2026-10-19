<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Models\WebsiteContent;

final readonly class ListWebsiteImages
{
    /** @return list<array{id: int, name: string, url: string}> */
    public function handle(WebsiteContent $content): array
    {
        $images = [];
        foreach ($content->getMedia('website-images') as $media) {
            $images[] = [
                'id' => (int) $media->id,
                'name' => $media->file_name,
                'url' => route('website.preview.image', ['image' => $media->id], absolute: false),
            ];
        }

        return $images;
    }
}
