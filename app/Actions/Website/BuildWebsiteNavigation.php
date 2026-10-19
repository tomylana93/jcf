<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Enums\WebsitePage;

final readonly class BuildWebsiteNavigation
{
    public function __construct(private GetPublicWebsiteContent $content) {}

    /**
     * @param  list<array{label: string, href: string}>  $links
     * @return list<array{label: string, href: string, active: bool}>
     */
    public function handle(array $links, string $activePath): array
    {
        if ($links === []) {
            $links = [
                ['label' => __('navigation.link.home'), 'href' => route('home', absolute: false)],
                ['label' => __('article.title.index'), 'href' => route('articles.index', absolute: false)],
                ['label' => __('contact.title.public'), 'href' => route('contact.index', absolute: false)],
            ];
            if ($this->content->handle('about') !== null) {
                $links[] = ['label' => WebsitePage::About->label(), 'href' => route('about', absolute: false)];
            }
        }

        return array_map(static fn (array $link): array => [...$link, 'active' => $link['href'] === $activePath], $links);
    }
}
