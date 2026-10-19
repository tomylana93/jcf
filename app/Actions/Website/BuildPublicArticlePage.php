<?php

declare(strict_types=1);

namespace App\Actions\Website;

final readonly class BuildPublicArticlePage
{
    public function __construct(private BuildPublicHome $home, private BuildArticlePageContent $page) {}

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>|null
     */
    public function handle(array $content): ?array
    {
        $website = $this->home->handle();
        if ($website === null) {
            return null;
        }
        $website['page'] = $this->page->handle($content, 'article');
        foreach ($website['navigation'] as &$item) {
            $item['active'] = $item['href'] === route('articles.index', absolute: false);
        }
        unset($item);
        $website['breadcrumbs'] = [
            ['label' => __('navigation.link.home'), 'href' => route('home', absolute: false), 'active' => false],
            ['label' => __('article.title.index'), 'href' => route('articles.index', absolute: false), 'active' => false],
            ['label' => $content['title'], 'href' => route('articles.show', ['slug' => $content['slug']], absolute: false), 'active' => true],
        ];

        return $website;
    }
}
