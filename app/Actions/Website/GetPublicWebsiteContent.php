<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Models\WebsiteContent;
use App\Models\WebsiteSlug;
use App\Settings\GeneralSettings;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

final readonly class GetPublicWebsiteContent
{
    public function __construct(private GeneralSettings $general) {}

    /** @return array{content: array<string, mixed>, redirect_slug: string|null}|null */
    public function handle(string $key, bool $article = false): ?array
    {
        if (! $this->general->public_pages_enabled) {
            return null;
        }
        $query = WebsiteContent::query()->select(['id', 'published', 'published_slug', 'published_at', 'publication_version'])->whereNotNull('published')->where('published_at', '<=', now());
        if ($article) {
            $slug = WebsiteSlug::query()->where('slug', $key)->where('was_published', true)->first();
            if ($slug === null) {
                return null;
            }
            $query->whereNull('page_key')->whereKey($slug->website_content_id);
        } else {
            $query->where('page_key', $key);
        }
        $record = $query->first();
        if ($record === null) {
            return null;
        }
        $content = Cache::remember('website.content.'.$record->id.'.'.$record->publication_version, 3600, static fn (): array => [
            ...Arr::only($record->published ?? [], ['kind', 'title', 'slug', 'excerpt', 'body_html', 'body_text', 'sections', 'seo', 'cover', 'author', 'featured']),
            'published_at' => $record->published_at?->toIso8601String(),
        ]);

        return ['content' => $content, 'redirect_slug' => $article && $record->published_slug !== $key ? $record->published_slug : null];
    }
}
