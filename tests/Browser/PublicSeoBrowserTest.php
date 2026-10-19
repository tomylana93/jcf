<?php

declare(strict_types=1);

use App\Actions\Website\PublishWebsiteContent;
use App\Actions\Website\SaveWebsiteDraft;
use App\Actions\Website\UploadWebsiteImage;
use App\Enums\Permission;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Settings\WebsiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Ssr\SsrState;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withUnencryptedCookie;

describe('Production public SEO and SSR', function (): void {
    it('renders initial HTTP HTML and hydrates every page with isolated heads', function (string $preset): void {
        $general = resolve(GeneralSettings::class);
        $general->public_pages_enabled = true;
        $general->save();
        $settings = resolve(WebsiteSettings::class);
        $settings->preset = $preset;
        $settings->save();
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $save = resolve(SaveWebsiteDraft::class);
        $publish = resolve(PublishWebsiteContent::class);
        foreach (['home', 'about', 'contact', 'article-index'] as $key) {
            $record = $save->handle($actor, Arr::except(websiteDraft(['title' => 'Published '.$key, 'seo' => ['title' => 'SEO '.$key, 'description' => 'Description '.$key], 'body_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Meaningful '.$key.' content']]]]]]), ['slug']), pageKey: $key);
            $publish->handle($actor, $record, 1);
        }
        $article = $save->handle($actor, websiteDraft(['title' => 'Published article', 'seo' => ['title' => 'SEO article', 'description' => 'Description article']]));
        Storage::fake('local');
        $media = resolve(UploadWebsiteImage::class)->handle($actor, $article, UploadedFile::fake()->image('social.png'));
        $article = $save->handle($actor, websiteDraft(['title' => 'Published article', 'seo' => ['title' => 'SEO article', 'description' => 'Description article', 'image' => ['media_id' => $media->id, 'alt' => 'Approved social image']]]), $article, 1);
        $publish->handle($actor, $article, 2);
        $save->handle($actor, websiteDraft(['title' => 'PRIVATE REVISION', 'seo' => ['title' => 'PRIVATE SEO']]), $article, 2);
        Http::allowStrayRequests(['http://127.0.0.1:13719/*']);
        $server = Process::path(base_path())->start(['node', 'tests/Browser/public-seo-server.mjs']);
        try {
            $server->waitUntil(fn (string $type, string $output): bool => str_contains($output, 'PUBLIC_SEO_SSR_READY'));
            expect(Http::get('http://127.0.0.1:13719/health')->successful())->toBeTrue();
            Inertia::disableSsr(false);
            config(['inertia.ssr.url' => 'http://127.0.0.1:13719', 'inertia.ssr.throw_on_error' => true]);
            $paths = ['home' => '/', 'about' => '/about', 'contact' => '/contact', 'article-index' => '/articles', 'article' => '/articles/company-news'];
            $payloads = [];
            foreach (['en', 'id', 'en'] as $locale) {
                withUnencryptedCookie('locale', $locale);
                foreach ($paths as $kind => $path) {
                    app()->forgetInstance(SsrState::class);
                    $response = get($path)->assertOk()->assertSeeHtml('<h1')->assertSee('Published '.$kind)->assertSeeHtml('name="description"')->assertSeeHtml('property="og:title"')->assertSeeHtml('name="twitter:card"')->assertSeeHtml('application/ld+json')->assertDontSee('PRIVATE SEO')->assertDontSee('PRIVATE REVISION');
                    $html = $response->getContent();
                    $head = explode('</head>', $html)[0];
                    expect(mb_substr_count($head, '<title'))->toBe(1)
                        ->and(mb_substr_count($head, 'rel="canonical"'))->toBe(1)
                        ->and(mb_substr_count($head, 'name="description"'))->toBe(1)
                        ->and($head)->toContain('SEO '.$kind, 'Description '.$kind)
                        ->and($html)->toContain('href="/articles"', 'href="/contact"')
                        ->and($response->viewData('page')['props']['website']['page']['seo']['locale'])->toBe($locale);
                    $payloads[] = $response->viewData('page');
                }
            }
            app()->forgetInstance(SsrState::class);
            get('/website/articles/'.$article->id.'/preview')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertSee('PRIVATE REVISION');
            // Repeat guest and authenticated payloads through the same long-lived renderer.
            auth()->forgetGuards();
            app()->forgetInstance(SsrState::class);
            $guest = get('/')->assertOk()->assertDontSee($actor->email);
            $payloads[] = $guest->viewData('page');
            actingAs(User::factory()->create(['name' => 'SECOND REQUEST USER']));
            app()->forgetInstance(SsrState::class);
            $second = get('/contact')->assertOk()->assertDontSee($actor->email);
            $payloads[] = $second->viewData('page');
            foreach (array_reverse($payloads) as $payload) {
                $rendered = Http::post('http://127.0.0.1:13719/render', $payload)->throw()->json();
                expect(implode('', $rendered['head']))->toContain('content="'.$payload['props']['website']['page']['seo']['locale'].'"')
                    ->and($rendered['body'])->toContain('Published '.$payload['props']['website']['page']['kind']);
            }
            app()->forgetInstance(SsrState::class);
            $page = visit('/')->assertSee('Published home')->assertNoJavaScriptErrors()->assertNoConsoleLogs();
            foreach (['/about', '/articles', '/articles/company-news', '/contact', '/', '/articles', '/articles/company-news', '/contact'] as $path) {
                $page->script("document.querySelector('a[href=\"{$path}\"]')?.click(); true;");
                $page->assertPathIs($path)->assertNoJavaScriptErrors()->assertNoConsoleLogs();
                $kind = array_search($path, $paths, true);
                expect($page->script("document.querySelectorAll('head meta[property=\"og:image\"]').length"))->toBe($kind === 'article' ? 1 : 0)
                    ->and($page->script("document.querySelectorAll('head meta[name=\"twitter:image:alt\"]').length"))->toBe($kind === 'article' ? 1 : 0);
                if ($kind === 'article') {
                    expect($page->script("document.querySelector('head meta[property=\"og:image:alt\"]').content"))->toBe('Approved social image');
                }
                expect($page->script("JSON.parse(document.querySelector('head script[type=\"application/ld+json\"]').textContent)['@context']"))->toBe('https://schema.org')
                    ->and($page->script('document.title'))->toBe('SEO '.$kind)
                    ->and($page->script("document.querySelector('head meta[name=description]').content"))->toBe('Description '.$kind)
                    ->and($page->script("document.querySelector('head meta[property=\"og:title\"]').content"))->toBe('SEO '.$kind)
                    ->and($page->script("document.querySelectorAll('head title').length"))->toBe(1)
                    ->and($page->script("document.querySelectorAll('head link[rel=canonical]').length"))->toBe(1)
                    ->and($page->script("document.querySelectorAll('head meta[name=description]').length"))->toBe(1)
                    ->and($page->script("document.querySelectorAll('head script[type=\"application/ld+json\"]').length"))->toBe(1);
            }
            expect($server->errorOutput())->toBeEmpty()
                ->and(Http::post('http://127.0.0.1:13719/render', ['component' => 'MissingSsrPage', 'props' => []])->status())->toBe(500)
                ->and(Http::get('http://127.0.0.1:13719/health')->successful())->toBeTrue();
        } finally {
            $server->stop();
        }
    })->with(['corporate', 'editorial', 'minimal']);
});
