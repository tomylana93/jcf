<?php

declare(strict_types=1);

use App\Actions\Website\BuildPublicAboutPage;
use App\Actions\Website\BuildPublicArticleIndex;
use App\Actions\Website\BuildPublicArticlePage;
use App\Actions\Website\BuildPublicContactPage;
use App\Actions\Website\PublishWebsiteContent;
use App\Actions\Website\ResolvePublicSeo;
use App\Actions\Website\SaveWebsiteDraft;
use App\Actions\Website\UploadWebsiteImage;
use App\Enums\Permission;
use App\Models\WebsiteContent;
use App\Models\WebsiteSlug;
use App\Settings\GeneralSettings;
use App\Settings\WebsiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

describe('Website publication acceptance', function (): void {
    it('returns unavailable page data independently of HTTP handling when public pages are disabled', function (string $builder, array $arguments): void {
        $general = resolve(GeneralSettings::class);
        $general->public_pages_enabled = false;
        $general->save();

        expect(resolve($builder)->handle(...$arguments))->toBeNull();
    })->with([
        'about' => [BuildPublicAboutPage::class, []],
        'contact' => [BuildPublicContactPage::class, []],
        'article index' => [BuildPublicArticleIndex::class, [1]],
        'article detail' => [BuildPublicArticlePage::class, [['title' => 'Unavailable article']]],
    ]);

    it('keeps structured page images and rendered sections consistent through preview and publication', function (string $key, string $path): void {
        Storage::fake('local');
        $general = resolve(GeneralSettings::class);
        $general->public_pages_enabled = true;
        $general->save();
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $save = resolve(SaveWebsiteDraft::class);
        $draft = Arr::except(websiteDraft(), ['slug']);
        $record = $save->handle($actor, $draft, pageKey: $key);
        $image = resolve(UploadWebsiteImage::class)->handle($actor, $record, UploadedFile::fake()->image('section.png'));
        $reference = ['media_id' => $image->id, 'alt' => 'Approved section illustration'];
        $sections = [
            ['id' => 'hero', 'type' => 'hero', 'visible' => true, 'title' => 'Approved hero', 'description' => 'Introduction', 'image' => $reference, 'cta' => ['label' => 'Contact', 'href' => '/contact']],
            ['id' => 'intro', 'type' => 'introduction', 'visible' => true, 'title' => 'Details', 'body_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => '<script>untrusted</script>']]]]]],
        ];

        post('/website/content/'.$key.'/draft', [...$draft, 'sections' => $sections, 'seo' => [...$draft['seo'], 'image' => $reference], 'version' => 1])->assertSessionHasNoErrors();
        get('/website/content/'.$key)->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->where('editor.content.version', 2)
            ->where('editor.images.0.id', $image->id)
            ->where('editor.images.0.name', $image->file_name)
            ->where('editor.images.0.url', route('website.preview.image', ['image' => $image->id], absolute: false)));
        get('/website/preview/'.$key)->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->where('website.page.sections.0.image.alt', $reference['alt'])
            ->where('website.page.sections.0.image.url', route('website.preview.image', ['image' => $image->id], absolute: false))
            ->where('website.page.sections.1.body', '<p>&lt;script&gt;untrusted&lt;/script&gt;</p>')
            ->missing('website.page.sections.1.body_json'));
        post('/website/content/'.$key.'/publish', ['version' => 2])->assertSessionHasNoErrors();
        auth()->forgetGuards();

        get($path)->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->where('website.page.sections.0.image.url', route('website.image', ['image' => $image->id], absolute: false))
            ->where('website.page.sections.0.image.alt', $reference['alt'])
            ->where('website.page.sections.1.body', '<p>&lt;script&gt;untrusted&lt;/script&gt;</p>')
            ->missing('website.page.sections.1.body_json'));
        get('/website/images/'.$image->id)->assertOk();
    })->with(['home' => ['home', '/'], 'about' => ['about', '/about'], 'contact' => ['contact', '/contact'], 'articles' => ['article-index', '/articles']]);

    it('shows an empty unpublished non-home preset preview without editor content', function (): void {
        actingAsUserWithPermissions(Permission::ConfigureWebsite);

        get('/website/preview/contact')->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->where('website.preview.mode', 'empty')
            ->where('website.page.sections', [])
            ->where('website.page.seo.robots', 'noindex,nofollow'));
    });

    it('rejects hidden missing and orphaned images while preserving private editor access', function (): void {
        Storage::fake('local');
        $general = resolve(GeneralSettings::class);
        $general->public_pages_enabled = true;
        $general->save();
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $save = resolve(SaveWebsiteDraft::class);
        $draft = Arr::except(websiteDraft(), ['slug']);
        $record = $save->handle($actor, $draft, pageKey: 'home');
        $image = resolve(UploadWebsiteImage::class)->handle($actor, $record, UploadedFile::fake()->image('hidden.png'));
        $section = ['id' => 'hidden', 'type' => 'hero', 'visible' => false, 'title' => 'Private illustration', 'description' => '', 'image' => ['media_id' => $image->id, 'alt' => 'Private image'], 'cta' => null];
        $record = $save->handle($actor, [...$draft, 'sections' => [$section]], $record, 1);
        resolve(PublishWebsiteContent::class)->handle($actor, $record, 2);

        get('/website/preview/images/'.$image->id)->assertOk();
        auth()->forgetGuards();
        get('/website/images/'.$image->id)->assertNotFound();
        get('/website/images/999999')->assertNotFound();
        $image->forceFill(['model_id' => (string) Str::uuid()])->save();
        get('/website/images/'.$image->id)->assertNotFound();
    });

    it('refuses publication when a stored draft slug belongs to another article', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $save = resolve(SaveWebsiteDraft::class);
        $first = $save->handle($actor, websiteDraft(['slug' => 'first-owner']));
        $other = $save->handle($actor, websiteDraft(['slug' => 'other-owner']));
        $first->update(['draft' => [...$first->draft, 'slug' => 'other-owner']]);

        expect(fn () => resolve(PublishWebsiteContent::class)->handle($actor, $first, 1))->toThrow(ValidationException::class, __('website.error.slug_taken'))
            ->and($first->fresh()->published)->toBeNull()
            ->and(WebsiteSlug::query()->where('slug', 'other-owner')->sole()->website_content_id)->toBe($other->id);
    });

    it('rejects malformed scalar sections without persisting a draft', function (): void {
        actingAsUserWithPermissions(Permission::EditWebsiteContent);

        post('/website/content/home/draft', [...Arr::except(websiteDraft(), ['slug']), 'sections' => ['malformed'], 'version' => 0])->assertSessionHasErrors(['sections.0']);

        expect(WebsiteContent::query()->count())->toBe(0);
    });

    it('leaves the SEO description empty when no factual fallback exists', function (): void {
        $general = resolve(GeneralSettings::class);
        $general->site_description = '';
        $general->save();
        $website = resolve(WebsiteSettings::class);
        $website->seo = [...$website->seo, 'description' => ''];
        $website->save();

        expect(resolve(ResolvePublicSeo::class)->handle([], 'home', 'Title')['description'])->toBe('');
    });

    it('refuses to publish a page revision that changed after review', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $save = resolve(SaveWebsiteDraft::class);
        $draft = Arr::except(websiteDraft(), ['slug']);
        $record = $save->handle($actor, $draft, pageKey: 'home');
        resolve(PublishWebsiteContent::class)->handle($actor, $record, 1);
        $publication = $record->refresh()->publication_version;
        $save->handle($actor, [...$draft, 'title' => 'Unreviewed revision'], $record, 1);

        post('/website/content/home/publish', ['version' => 1])
            ->assertSessionHasErrors(['version' => __('website.error.stale_draft')]);

        expect($record->refresh()->published['title'])->toBe('Company news')
            ->and($record->publication_version)->toBe($publication)
            ->and($record->draft_version)->toBe(2);

        post('/website/content/home/publish', ['version' => 2])->assertSessionHasNoErrors();
        expect($record->refresh()->published['title'])->toBe('Unreviewed revision');
    });

    it('requires a valid reviewed page version before publishing', function (array $input): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $record = resolve(SaveWebsiteDraft::class)->handle($actor, Arr::except(websiteDraft(), ['slug']), pageKey: 'home');

        post('/website/content/home/publish', $input)->assertSessionHasErrors('version');

        expect($record->refresh()->published)->toBeNull();
    })->with(['missing' => [[]], 'zero' => [['version' => 0]], 'non-integer' => [['version' => 'invalid']]]);

    it('uses configured navigation and footer links on every public page', function (string $path, int $active): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent, Permission::ConfigureWebsite);
        $save = resolve(SaveWebsiteDraft::class);
        $about = $save->handle($actor, Arr::except(websiteDraft(), ['slug']), pageKey: 'about');
        resolve(PublishWebsiteContent::class)->handle($actor, $about, 1);
        $article = $save->handle($actor, websiteDraft());
        resolve(PublishWebsiteContent::class)->handle($actor, $article, 1);
        $links = [
            ['label' => 'Get in touch', 'href' => '/contact'],
            ['label' => 'Organization', 'href' => '/about'],
            ['label' => 'Start', 'href' => '/'],
            ['label' => 'Newsroom', 'href' => '/articles'],
            ['label' => 'External', 'href' => 'https://example.com'],
        ];
        $footer = ['text' => 'Approved footer', 'links' => [['label' => 'Privacy', 'href' => 'https://example.com/privacy']]];
        patch('/website/settings', ['navigation' => $links, 'footer' => $footer])->assertSessionHasNoErrors();
        auth()->forgetGuards();

        get($path)->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->where('website.navigation', array_map(static fn (array $link, int $index): array => [...$link, 'active' => $index === $active], $links, array_keys($links)))
            ->where('website.footer', $footer));
    })->with(['home' => ['/', 2], 'about' => ['/about', 1], 'contact' => ['/contact', 0], 'articles' => ['/articles', 3], 'article' => ['/articles/company-news', 3]]);

    it('previews published recent and featured articles without exposing private revisions', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent, Permission::ConfigureWebsite);
        $save = resolve(SaveWebsiteDraft::class);
        $article = $save->handle($actor, websiteDraft(['featured' => true]));
        resolve(PublishWebsiteContent::class)->handle($actor, $article, 1);
        $save->handle($actor, websiteDraft(['title' => 'Private revision', 'featured' => true]), $article, 1);
        $save->handle($actor, websiteDraft(['slug' => 'private', 'title' => 'Private article']));
        $future = $save->handle($actor, websiteDraft(['slug' => 'future', 'title' => 'Future article']));
        resolve(PublishWebsiteContent::class)->handle($actor, $future, 1, now()->addDay()->toIso8601String());
        $sections = [['id' => 'news', 'type' => 'articles', 'title' => 'Featured news', 'visible' => true, 'featured' => true, 'limit' => 6]];
        $save->handle($actor, Arr::except(websiteDraft(['sections' => $sections]), ['slug']), pageKey: 'home');
        patch('/website/settings', ['navigation' => [['label' => 'Newsroom', 'href' => '/articles']], 'footer' => ['text' => 'Preview footer', 'links' => [['label' => 'Privacy', 'href' => '/privacy']]]])->assertSessionHasNoErrors();
        $general = resolve(GeneralSettings::class);
        $general->public_pages_enabled = false;
        $general->save();

        get('/website/preview/home?preset=editorial')->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->where('website.preset', 'editorial')
            ->where('website.page.sections.0.type', 'articles')
            ->has('website.articles', 1)
            ->has('website.featuredArticles', 1)
            ->where('website.articles.0.title', 'Company news')
            ->where('website.featuredArticles.0.title', 'Company news')
            ->where('website.navigation.0.label', 'Newsroom')
            ->where('website.footer.links.0.href', '/privacy')
            ->where('website.page.seo.robots', 'noindex,nofollow'));
    });

    it('returns rejected rich text on the editor field without changing the saved draft', function (bool $article, bool $section): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent);
        $draft = $article ? websiteDraft() : Arr::except(websiteDraft(), ['slug']);
        $record = resolve(SaveWebsiteDraft::class)->handle($actor, $draft, pageKey: $article ? null : 'home');
        $invalid = ['type' => 'script'];
        $input = [...$draft, 'version' => 1];
        if ($article) {
            $input = [...$input, 'author_id' => $actor->id, 'featured' => false];
        }
        if ($section) {
            $input['sections'] = [['id' => 'intro', 'type' => 'introduction', 'visible' => true, 'title' => 'Introduction', 'body_json' => $invalid]];
        } else {
            $input['body_json'] = $invalid;
        }

        post($article ? '/website/articles/'.$record->id.'/draft' : '/website/content/home/draft', $input)
            ->assertSessionHasErrors([$section ? 'sections.0.body_json' : 'body_json' => __('documentation.error.invalid_content')])
            ->assertSessionDoesntHaveErrors('content_json');

        expect($record->refresh()->draft_version)->toBe(1)
            ->and($record->draft['title'])->toBe('Company news')
            ->and($record->draft['body_html'])->toBe('<p>&lt;script&gt;example&lt;/script&gt;</p>');
    })->with(['page body' => [false, false], 'article body' => [true, false], 'page section' => [false, true]]);
});
