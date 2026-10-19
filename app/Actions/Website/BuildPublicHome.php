<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Settings\GeneralSettings;

final readonly class BuildPublicHome
{
    public function __construct(private GetPublicWebsiteContent $content, private GetPublicWebsiteSettings $settings, private GeneralSettings $general, private GetPublicArticles $articles, private ResolvePublicSeo $seo, private BuildWebsiteNavigation $navigation) {}

    /** @return array<string, mixed>|null */
    public function handle(): ?array
    {
        $settings = $this->settings->handle();
        if ($settings === null) {
            return null;
        }
        $content = $this->content->handle('home')['content'] ?? null;
        $title = $content['title'] ?? ($settings['identity'] ?: $this->general->site_name);
        $sections = $content['sections'] ?? [];
        foreach ($sections as &$section) {
            if (isset($section['image'])) {
                $section['image'] = $this->image($section['image']);
            }
        }
        unset($section);
        if ($content === null) {
            $sections = [['id' => 'public-introduction', 'type' => 'hero', 'visible' => true, 'title' => $title, 'description' => $this->general->site_description, 'image' => null, 'cta' => ['label' => __('navigation.link.explore'), 'href' => '#public-introduction', 'active' => false]]];
        }

        return [
            'preset' => $settings['preset'],
            'preferences' => $settings['preferences'],
            'navigation' => $this->navigation->handle($settings['navigation'], route('home', absolute: false)),
            'articles' => $this->articles->recent(),
            'featuredArticles' => $this->articles->recent(true),
            'footer' => $settings['footer'],
            'page' => [
                'kind' => 'home', 'title' => $title, 'sections' => $sections,
                'body' => $content['body_html'] ?? '',
                'cover' => $this->image($content['cover'] ?? null),
                'seo' => $this->seo->handle($content ?? [], 'home', $title),
            ],
        ];
    }

    /** @param array{media_id: int, alt: string}|null $image
     * @return array{url: string, alt: string}|null
     */
    private function image(?array $image): ?array
    {
        return $image === null ? null : ['url' => route('website.image', ['image' => $image['media_id']], absolute: false), 'alt' => $image['alt']];
    }
}
