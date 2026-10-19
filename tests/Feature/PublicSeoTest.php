<?php

declare(strict_types=1);

use App\Actions\Website\PublishWebsiteContent;
use App\Actions\Website\ResolvePublicSeo;
use App\Actions\Website\SaveWebsiteDraft;
use App\Actions\Website\UnpublishWebsiteContent;
use App\Enums\Permission;
use App\Settings\BrandSettings;
use App\Settings\GeneralSettings;
use App\Settings\WebsiteSettings;
use Illuminate\Support\Arr;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

describe('Public indexing and metadata', function (): void {
    beforeEach(function (): void {
        $general = resolve(GeneralSettings::class);
        $general->public_pages_enabled = true;
        $general->save();
        config(['app.url' => 'https://trusted.example']);
    });

    it('resolves explicit content and website fallbacks with safe structured data', function (): void {
        $settings = resolve(WebsiteSettings::class);
        $settings->identity = 'Real organization';
        $settings->seo = ['title' => 'Website title', 'description' => 'Website description', 'robots' => 'index,follow'];
        $settings->save();
        $resolver = resolve(ResolvePublicSeo::class);
        $seo = $resolver->handle(['title' => 'Content', 'slug' => 'sample', 'seo' => ['title' => '</script><script>bad()</script>', 'description' => 'Override'], 'cover' => ['media_id' => 123, 'alt' => 'Actual image'], 'author' => 'Public author', 'published_at' => '2026-10-01T00:00:00Z'], 'article', 'Content');
        expect($seo['title'])->toBe('</script><script>bad()</script>')
            ->and($seo['description'])->toBe('Override')
            ->and($seo['canonicalUrl'])->toBe('https://trusted.example/articles/sample')
            ->and($seo['image'])->toBe(['url' => 'https://trusted.example/website/images/123', 'alt' => 'Actual image'])
            ->and($seo['structuredData'])->not->toContain('<script', '</script>');
        $graph = json_decode($seo['structuredData'], true, flags: JSON_THROW_ON_ERROR)['@graph'];
        expect(array_column($graph, '@type'))->toBe(['Organization', 'WebSite', 'BreadcrumbList', 'Article'])
            ->and($graph[3]['author']['name'])->toBe('Public author')
            ->and($resolver->handle(['seo' => ['title' => '', 'description' => null], 'excerpt' => 'Approved summary'], 'home', 'Content')['description'])->toBe('Approved summary')
            ->and($resolver->handle(['body_text' => 'Readable body'], 'about', 'About')['description'])->toBe('Readable body')
            ->and($resolver->handle([], 'home', '')['title'])->toBe('Website title')
            ->and($resolver->handle([], 'home', '')['description'])->toBe('Website description');
        $brand = resolve(BrandSettings::class);
        $brand->logo_path = 'brand/social.png';
        $brand->save();
        expect($resolver->handle([], 'home', '')['image']['alt'])->toBe('Real organization');
        $preview = $resolver->handle(['seo' => ['robots' => 'index,follow']], 'contact', 'Contact', true);
        expect($preview['robots'])->toBe('noindex,nofollow')->and($preview['structuredData'])->toBe('');
    });

    it('keeps configured canonicals and pagination identity while ignoring tracking and filters', function (): void {
        foreach (['/?utm_source=test', '/contact?search=test', '/articles?page=2&utm_campaign=x&filter[search]=secret'] as $url) {
            $expected = str_starts_with($url, '/articles') ? '/articles?page=2' : (str_starts_with($url, '/contact') ? '/contact' : '/');
            get('https://untrusted.example'.$url)->assertOk()->assertInertia(fn (Assert $page) => $page->where('website.page.seo.canonicalUrl', 'https://trusted.example'.$expected));
        }
        get('/articles?page=1')->assertInertia(fn (Assert $page) => $page->where('website.page.seo.canonicalUrl', 'https://trusted.example/articles'));
    });

    it('updates sitemap immediately through publication drafts renames noindex and withdrawal', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $save = resolve(SaveWebsiteDraft::class);
        $publish = resolve(PublishWebsiteContent::class);
        $article = $save->handle($actor, websiteDraft());
        $about = $save->handle($actor, Arr::except(websiteDraft(['title' => 'About organization']), ['slug']), pageKey: 'about');
        get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->assertDontSee('/articles/company-news')->assertDontSeeHtml('<loc>https://trusted.example/about</loc>');
        get('/about')->assertNotFound();
        $publish->handle($actor, $article, 1);
        $publish->handle($actor, $about, 1);
        get('/sitemap.xml')->assertSeeHtml('<loc>https://trusted.example/about</loc>')->assertSeeHtml('<loc>https://trusted.example/articles/company-news</loc>')->assertDontSee('/website/');
        get('/about')->assertOk()->assertInertia(fn (Assert $page) => $page->where('website.page.title', 'About organization'));
        $article = $save->handle($actor, websiteDraft(['slug' => 'new-address', 'title' => 'Private changes']), $article, 1);
        get('/sitemap.xml')->assertSee('/articles/company-news')->assertDontSee('new-address')->assertDontSee('Private changes');
        $publish->handle($actor, $article, 2);
        get('/sitemap.xml')->assertSee('/articles/new-address')->assertDontSee('/articles/company-news');
        get('/articles/company-news')->assertStatus(301);
        $article = $save->handle($actor, websiteDraft(['slug' => 'new-address', 'seo' => ['robots' => 'noindex,nofollow']]), $article, 2);
        $publish->handle($actor, $article, 3);
        get('/sitemap.xml')->assertDontSee('/articles/new-address');
        get('/articles/new-address')->assertInertia(fn (Assert $page) => $page->where('website.page.seo.robots', 'noindex,nofollow'));
        resolve(UnpublishWebsiteContent::class)->handle($actor, $about);
        get('/sitemap.xml')->assertDontSeeHtml('<loc>https://trusted.example/about</loc>');
        get('/about')->assertNotFound();
        $future = $save->handle($actor, websiteDraft(['slug' => 'future-news']));
        $publish->handle($actor, $future, 1, now()->addDay()->toIso8601String());
        get('/sitemap.xml')->assertDontSee('future-news');
    });

    it('aligns robots and sitemap with the public switch and indexing defaults', function (): void {
        get('/robots.txt')->assertOk()->assertSee('Sitemap: https://trusted.example/sitemap.xml')->assertSee('Disallow: /website');
        $general = resolve(GeneralSettings::class);
        $general->public_pages_enabled = false;
        $general->save();
        get('/robots.txt')->assertSee("Disallow: /\n");
        get('/sitemap.xml')->assertDontSeeHtml('<url>');
        $general->public_pages_enabled = true;
        $general->save();
        $settings = resolve(WebsiteSettings::class);
        $settings->seo['robots'] = 'noindex,nofollow';
        $settings->save();
        get('/sitemap.xml')->assertDontSeeHtml('<url>');
        get('/robots.txt')->assertDontSee("Disallow: /\n");
        get('/website/preview/home')->assertRedirect();
    });
});
