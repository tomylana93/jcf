<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Models\User;
use App\Models\WebsiteContent;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final readonly class GetWebsiteImage
{
    public function __construct(private GeneralSettings $general) {}

    public function handle(int $id, ?User $actor = null): ?Media
    {
        $media = Media::query()->where('model_type', (new WebsiteContent)->getMorphClass())->where('collection_name', 'website-images')->find($id);
        if ($media === null) {
            return null;
        }
        $content = WebsiteContent::query()->find($media->model_id);
        if ($content === null) {
            return null;
        }
        if ($actor !== null && Gate::forUser($actor)->allows('preview', $content)) {
            return $media;
        }
        if (! $this->general->public_pages_enabled || $content->published === null || $content->published_at === null || $content->published_at->isFuture()) {
            return null;
        }
        $images = [$content->published['cover'] ?? null, $content->published['seo']['image'] ?? null];
        foreach ($content->published['sections'] as $section) {
            if ($section['visible']) {
                $images[] = $section['image'] ?? null;
            }
        }
        foreach ($images as $image) {
            if (($image['media_id'] ?? null) === $id) {
                return $media;
            }
        }

        return null;
    }
}
