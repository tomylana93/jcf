<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Settings\BrandSettings;
use App\Settings\GeneralSettings;
use App\Settings\WebsiteSettings;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class ResolvePublicSeo
{
    public function __construct(private WebsiteSettings $settings, private GeneralSettings $general, private BrandSettings $brand) {}

    /**
     * @param  array<string, mixed>  $content  Published snapshot, or authorized preview only.
     * @return array<string, mixed>
     */
    public function handle(array $content, string $kind, string $title, bool $preview = false, int $page = 1): array
    {
        $this->settings->refresh();
        $siteName = $this->settings->identity ?: $this->general->site_name;
        $title = $this->text($content['seo']['title'] ?? '', $title, $this->settings->seo['title'], $siteName);
        $description = $this->text($content['seo']['description'] ?? '', $content['excerpt'] ?? '', $content['body_text'] ?? '', $this->settings->seo['description'], $this->general->site_description);
        $description = Str::limit($description, 300, '…');
        $canonical = $this->canonical($kind, $content['slug'] ?? null, $page);
        $robots = $preview ? 'noindex,nofollow' : ($content['seo']['robots'] ?? $this->settings->seo['robots']);
        $image = $content['seo']['image'] ?? $content['cover'] ?? null;
        $image = $image === null ? null : ['url' => $this->absolute(route($preview ? 'website.preview.image' : 'website.image', ['image' => $image['media_id']], absolute: false)), 'alt' => $image['alt']];
        if ($image === null && ! $preview) {
            $this->brand->refresh();
            if ($this->brand->logo_path !== null) {
                $url = Storage::disk('public')->url($this->brand->logo_path);
                $image = ['url' => str_starts_with($url, '/') ? $this->absolute($url) : $url, 'alt' => $siteName];
            }
        }
        $home = $this->canonical('home');
        $graph = [
            ['@type' => 'Organization', '@id' => $home.'#organization', 'name' => $siteName, 'url' => $home],
            ['@type' => 'WebSite', '@id' => $home.'#website', 'name' => $siteName, 'url' => $home],
        ];
        if ($kind !== 'home') {
            $items = [['@type' => 'ListItem', 'position' => 1, 'name' => __('navigation.link.home'), 'item' => $home]];
            if ($kind === 'article') {
                $items[] = ['@type' => 'ListItem', 'position' => 2, 'name' => __('article.title.index'), 'item' => $this->canonical('article-index')];
            }
            $items[] = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => $title, 'item' => $canonical];
            $graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
        }
        if ($kind === 'article' && ! $preview) {
            $article = ['@type' => 'Article', 'headline' => $title, 'description' => $description, 'mainEntityOfPage' => $canonical, 'publisher' => ['@id' => $home.'#organization']];
            if (! empty($content['published_at'])) {
                $article['datePublished'] = $content['published_at'];
            }
            if (! empty($content['author'])) {
                $article['author'] = ['@type' => 'Person', 'name' => $content['author']];
            }
            if ($image !== null) {
                $article['image'] = $image['url'];
            }
            $graph[] = $article;
        }

        return [
            'title' => $title, 'description' => $description, 'canonicalUrl' => $canonical,
            'robots' => $robots, 'locale' => app()->getLocale(), 'image' => $image,
            'siteName' => $siteName, 'type' => $kind === 'article' ? 'article' : 'website',
            'structuredData' => $preview ? '' : json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
        ];
    }

    public function canonical(string $kind, ?string $slug = null, int $page = 1): string
    {
        $route = match ($kind) {
            'about' => 'about', 'contact' => 'contact.index', 'article-index' => 'articles.index', 'article' => 'articles.show', default => 'home',
        };
        $parameters = $kind === 'article' ? ['slug' => $slug] : [];
        if ($kind === 'article-index' && $page > 1) {
            $parameters['page'] = $page;
        }

        return $this->absolute(route($route, $parameters, absolute: false));
    }

    private function absolute(string $path): string
    {
        return mb_rtrim((string) config('app.url'), '/').'/'.mb_ltrim($path, '/');
    }

    private function text(string ...$values): string
    {
        foreach ($values as $value) {
            $text = mb_trim(preg_replace('/\s+/u', ' ', $value) ?? '');
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }
}
