<?php

declare(strict_types=1);

namespace App\Actions\Website;

use Illuminate\Support\Facades\Date;

final readonly class BuildArticlePageContent
{
    public function __construct(private GetPublicArticles $articles, private ResolvePublicSeo $seo) {}

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public function handle(array $content, string $kind, bool $preview = false, int $number = 1): array
    {
        $title = $content['title'];
        $page = ['kind' => $kind, 'title' => $title, 'sections' => [], 'body' => $content['body_html'] ?? '', 'cover' => $this->articles->image($content['cover'] ?? null, $preview), 'seo' => $this->seo->handle($content, $kind, $title, $preview, $number)];
        if ($kind === 'article-index') {
            $page['sections'] = $content['sections'] ?? [];
            foreach ($page['sections'] as &$section) {
                if (isset($section['image'])) {
                    $section['image'] = $this->articles->image($section['image'], $preview);
                }
            }
            unset($section);
        }
        if ($kind === 'article') {
            $date = isset($content['published_at']) ? Date::parse($content['published_at']) : null;
            $page['article'] = ['author' => $content['author'] ?? '', 'publishedAt' => $date?->toIso8601String() ?? '', 'publishedLabel' => $date?->settings(['locale' => app()->getLocale()])->isoFormat('LL') ?? ''];
        }

        return $page;
    }
}
