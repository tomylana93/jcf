<?php

declare(strict_types=1);

namespace App\Actions\Website;

final readonly class BuildPublicContactPage
{
    public function __construct(private BuildPublicHome $home, private GetPublicWebsiteContent $content, private GetPublicWebsiteSettings $settings, private GetPublicArticles $articles, private ResolvePublicSeo $seo) {}

    /** @return array<string, mixed>|null */
    public function handle(): ?array
    {
        $website = $this->home->handle();
        if ($website === null) {
            return null;
        }
        $settings = $this->settings->handle();
        $content = $this->content->handle('contact')['content'] ?? null;
        $title = $content['title'] ?? __('contact.title.public');
        $sections = $content['sections'] ?? [];
        foreach ($sections as &$section) {
            if (isset($section['image'])) {
                $section['image'] = $this->articles->image($section['image']);
            }
        }
        unset($section);
        $website['page'] = [
            'kind' => 'contact', 'title' => $title, 'sections' => $sections, 'body' => $content['body_html'] ?? '',
            'cover' => $this->articles->image($content['cover'] ?? null),
            'seo' => $this->seo->handle($content ?? [], 'contact', $title),
        ];
        $website['contact'] = $settings['contact'];
        foreach ($website['navigation'] as &$item) {
            $item['active'] = $item['href'] === route('contact.index', absolute: false);
        }
        unset($item);

        return $website;
    }
}
