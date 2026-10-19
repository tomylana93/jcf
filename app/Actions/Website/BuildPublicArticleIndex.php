<?php

declare(strict_types=1);

namespace App\Actions\Website;

final readonly class BuildPublicArticleIndex
{
    public function __construct(private BuildPublicHome $home, private GetPublicArticles $articles, private GetPublicWebsiteContent $content, private BuildArticlePageContent $page) {}

    /** @return array<string, mixed>|null */
    public function handle(int $number): ?array
    {
        $website = $this->home->handle();
        if ($website === null) {
            return null;
        }
        $content = $this->content->handle('article-index')['content'] ?? ['title' => __('article.title.index')];
        $website['page'] = $this->page->handle($content, 'article-index', number: $number);
        foreach ($website['navigation'] as &$item) {
            $item['active'] = $item['href'] === route('articles.index', absolute: false);
        }
        unset($item);
        $paginator = $this->articles->handle($number);
        $website['articles'] = $paginator->getCollection()->map($this->articles->card(...))->all();
        $pages = array_unique([1, max(1, $paginator->currentPage() - 1), $paginator->currentPage(), min($paginator->lastPage(), $paginator->currentPage() + 1), $paginator->lastPage()]);
        $website['pagination'] = array_map(static fn (int $n): array => ['label' => (string) $n, 'href' => route('articles.index', ['page' => $n], absolute: false), 'active' => $n === $paginator->currentPage()], $pages);
        $website['breadcrumbs'] = [
            ['label' => __('navigation.link.home'), 'href' => route('home', absolute: false), 'active' => false],
            ['label' => __('article.title.index'), 'href' => route('articles.index', absolute: false), 'active' => false],
        ];

        return $website;
    }
}
