<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Models\WebsiteContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class GetPublicArticles
{
    /** @return Builder<WebsiteContent> */
    public function query(): Builder
    {
        return WebsiteContent::query()->whereNull('page_key')->whereNotNull('published')->whereNotNull('published_slug')->where('published_at', '<=', now())->select(['id', 'published', 'published_slug', 'published_at'])->latest('published_at')->orderBy('id');
    }

    /** @return LengthAwarePaginator<int, WebsiteContent> */
    public function handle(int $page): LengthAwarePaginator
    {
        return $this->query()->paginate(12, page: max(1, $page));
    }

    /** @return list<array<string, mixed>> */
    public function recent(bool $featured = false): array
    {
        $query = $this->query();
        if ($featured) {
            $query->where('published->featured', true);
        }

        return array_values($query->limit(12)->get()->map($this->card(...))->all());
    }

    /** @return array<string, mixed> */
    public function card(WebsiteContent $article): array
    {
        $snapshot = $article->published ?? [];

        return ['id' => $article->id, 'title' => $snapshot['title'], 'excerpt' => $snapshot['excerpt'] ?? '', 'href' => route('articles.show', ['slug' => $article->published_slug], absolute: false), 'cover' => $this->image($snapshot['cover'] ?? null), 'featured' => (bool) ($snapshot['featured'] ?? false), 'publishedAt' => $article->published_at?->toIso8601String(), 'publishedLabel' => $article->published_at?->settings(['locale' => app()->getLocale()])->isoFormat('LL')];
    }

    /** @param array{media_id: int, alt: string}|null $image
     * @return array{url: string, alt: string}|null
     */
    public function image(?array $image, bool $preview = false): ?array
    {
        return $image === null ? null : ['url' => route($preview ? 'website.preview.image' : 'website.image', ['image' => $image['media_id']], absolute: false), 'alt' => $image['alt']];
    }
}
