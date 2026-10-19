<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Models\User;
use App\Models\WebsiteContent;
use App\Settings\GeneralSettings;
use App\Settings\WebsiteSettings;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;

final readonly class BuildPublicArticlePage
{
    public function __construct(private BuildPublicHome $home, private GetPublicArticles $articles, private GetPublicWebsiteContent $content, private ValidateWebsiteContent $validate, private WebsiteSettings $settings, private GeneralSettings $general) {}

    /** @return array<string, mixed> */
    public function index(int $number): array
    {
        $website = $this->home->handle();
        abort_if($website === null, 404);
        $content = $this->content->handle('article-index')['content'] ?? ['title' => __('article.title.index')];
        $website['page'] = $this->page($content, 'article-index');
        $website['navigation'][0]['active'] = false;
        $website['navigation'][1]['active'] = true;
        $paginator = $this->articles->handle($number);
        $website['articles'] = $paginator->getCollection()->map($this->articles->card(...))->all();
        $pages = array_unique([1, max(1, $paginator->currentPage() - 1), $paginator->currentPage(), min($paginator->lastPage(), $paginator->currentPage() + 1), $paginator->lastPage()]);
        $website['pagination'] = array_map(static fn (int $n): array => ['label' => (string) $n, 'href' => route('articles.index', ['page' => $n], absolute: false), 'active' => $n === $paginator->currentPage()], $pages);
        if ($number > 1) {
            $website['page']['seo']['canonicalUrl'] = route('articles.index', ['page' => $number]);
        }
        $website['breadcrumbs'] = $this->breadcrumbs();

        return $website;
    }

    /** @param array<string, mixed> $content
     * @return array<string, mixed>
     */
    public function show(array $content): array
    {
        $website = $this->home->handle();
        abort_if($website === null, 404);
        $website['page'] = $this->page($content, 'article');
        $website['navigation'][0]['active'] = false;
        $website['navigation'][1]['active'] = true;
        $website['breadcrumbs'] = [...$this->breadcrumbs(), ['label' => $content['title'], 'href' => route('articles.show', ['slug' => $content['slug']], absolute: false), 'active' => true]];

        return $website;
    }

    /** @return array<string, mixed> */
    public function preview(WebsiteContent $article): array
    {
        $this->settings->refresh();
        $content = $this->validate->handle(Arr::except($article->draft, ['body_html', 'body_text']), $article, true);
        $content['author'] = User::query()->whereKey($article->author_id)->value('name') ?? '';
        $website = ['preset' => $this->settings->preset, 'preferences' => $this->settings->preferences, 'navigation' => [], 'footer' => ['text' => $this->settings->footer['text'], 'links' => []], 'preview' => ['mode' => 'draft']];
        $website['page'] = $this->page($content, 'article', true);

        return $website;
    }

    /** @param array<string, mixed> $content
     * @return array<string, mixed>
     */
    private function page(array $content, string $kind, bool $preview = false): array
    {
        $this->settings->refresh();
        $title = $content['title'];
        $page = ['kind' => $kind, 'title' => $title, 'sections' => [], 'body' => $content['body_html'] ?? '', 'cover' => $this->articles->image($content['cover'] ?? null, $preview), 'seo' => [
            'title' => ($content['seo']['title'] ?? '') ?: $title,
            'description' => ($content['seo']['description'] ?? '') ?: (($content['excerpt'] ?? '') ?: ($this->settings->seo['description'] ?: $this->general->site_description)),
            'robots' => $preview ? 'noindex,nofollow' : ($content['seo']['robots'] ?? $this->settings->seo['robots']),
            'canonicalUrl' => $kind === 'article' ? route('articles.show', ['slug' => $content['slug']]) : route('articles.index'),
            'locale' => app()->getLocale(), 'image' => $this->articles->image($content['seo']['image'] ?? null, $preview),
        ]];
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

    /** @return list<array{label: string, href: string, active: bool}> */
    private function breadcrumbs(): array
    {
        return [['label' => __('navigation.link.home'), 'href' => route('home', absolute: false), 'active' => false], ['label' => __('article.title.index'), 'href' => route('articles.index', absolute: false), 'active' => false]];
    }
}
