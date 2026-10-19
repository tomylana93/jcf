<?php

declare(strict_types=1);

namespace App\Actions\Website;

final readonly class BuildPublicAboutPage
{
    public function __construct(private BuildPublicHome $home, private GetPublicWebsiteContent $content, private GetPublicArticles $articles, private ResolvePublicSeo $seo) {}

    /** @return array<string, mixed>|null */
    public function handle(): ?array
    {
        $content = $this->content->handle('about')['content'] ?? null;
        $website = $content === null ? null : $this->home->handle();
        if ($content === null || $website === null) {
            return null;
        }
        $sections = $content['sections'] ?? [];
        foreach ($sections as &$section) {
            if (isset($section['image'])) {
                $section['image'] = $this->articles->image($section['image']);
            }
        }
        unset($section);
        $website['page'] = ['kind' => 'about', 'title' => $content['title'], 'body' => $content['body_html'] ?? '', 'cover' => $this->articles->image($content['cover'] ?? null), 'sections' => $sections, 'seo' => $this->seo->handle($content, 'about', $content['title'])];
        foreach ($website['navigation'] as &$item) {
            $item['active'] = $item['href'] === route('about', absolute: false);
        }
        unset($item);

        return $website;
    }
}
