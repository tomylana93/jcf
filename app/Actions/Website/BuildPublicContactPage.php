<?php

declare(strict_types=1);

namespace App\Actions\Website;

final readonly class BuildPublicContactPage
{
    public function __construct(private BuildPublicHome $home, private GetPublicWebsiteContent $content, private GetPublicWebsiteSettings $settings, private GetPublicArticles $articles) {}

    /** @return array<string, mixed> */
    public function handle(): array
    {
        $website = $this->home->handle();
        abort_if($website === null, 404);
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
            'seo' => [
                'title' => $content['seo']['title'] ?? $title,
                'description' => $content['seo']['description'] ?? $settings['seo']['description'],
                'canonicalUrl' => route('contact.index'), 'robots' => $content['seo']['robots'] ?? $settings['seo']['robots'],
                'locale' => app()->getLocale(), 'image' => $this->articles->image($content['seo']['image'] ?? null),
            ],
        ];
        $website['contact'] = $settings['contact'];
        foreach ($website['navigation'] as &$item) {
            $item['active'] = $item['href'] === route('contact.index', absolute: false);
        }
        unset($item);

        return $website;
    }
}
