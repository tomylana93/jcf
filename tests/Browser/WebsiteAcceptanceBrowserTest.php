<?php

declare(strict_types=1);

use App\Actions\Website\PublishWebsiteContent;
use App\Actions\Website\SaveWebsiteDraft;
use App\Actions\Website\UploadWebsiteImage;
use App\Enums\Permission;
use App\Models\User;
use App\Models\WebsiteContent;
use App\Settings\GeneralSettings;
use App\Settings\WebsiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

describe('Public website acceptance', function (): void {
    $enablePublicPages = function (): void {
        $general = resolve(GeneralSettings::class);
        $general->public_pages_enabled = true;
        $general->save();
    };

    $publishPage = function (User $actor, string $pageKey, string $title): WebsiteContent {
        $record = resolve(SaveWebsiteDraft::class)->handle(
            $actor,
            Arr::except(websiteDraft(['title' => $title]), ['slug']),
            pageKey: $pageKey,
        );

        return resolve(PublishWebsiteContent::class)->handle($actor, $record, $record->draft_version);
    };

    $publishArticle = function (User $actor, string $slug, string $title): WebsiteContent {
        Storage::fake('local');
        $save = resolve(SaveWebsiteDraft::class);
        $draft = websiteDraft([
            'title' => $title,
            'slug' => $slug,
            'body_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Published article body for acceptance checks.']]]]],
        ]);
        $article = $save->handle($actor, $draft);
        $cover = resolve(UploadWebsiteImage::class)->handle($actor, $article, UploadedFile::fake()->image('acceptance-cover.jpg', 1200, 800));
        $article = $save->handle($actor, [...$draft, 'cover' => ['media_id' => $cover->id, 'alt' => 'Acceptance cover alt']], $article, $article->draft_version);

        return resolve(PublishWebsiteContent::class)->handle($actor, $article, $article->draft_version);
    };

    it('publishes a home draft from the admin editor and serves it to guests', function () use ($enablePublicPages): void {
        $enablePublicPages();
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);

        visit('/website/content/home')
            ->assertSee(__('enums.website_page.Home'))
            ->type('#website_page_title', 'Acceptance home title')
            ->click('@save_website_draft')
            ->assertSee(__('website.helper.draft_saved', ['version' => 1]))
            ->assertValue('#website_page_title', 'Acceptance home title')
            ->assertNoJavaScriptErrors();

        $record = WebsiteContent::query()->where('page_key', 'home')->sole();
        expect($record->draft['title'])->toBe('Acceptance home title')
            ->and($record->published)->toBeNull();

        visit('/website/preview/home')
            ->assertSee('Acceptance home title')
            ->assertSee(__('website.status.preview_draft'))
            ->assertAttribute('head meta[name="robots"]', 'content', 'noindex,nofollow')
            ->assertNoJavaScriptErrors();

        visit('/website/content/home')
            ->click('@request_publish_website')
            ->click('@confirm_publish_website')
            ->assertSee(__('website.status.published'))
            ->assertNoJavaScriptErrors();

        expect($record->fresh()->published['title'])->toBe('Acceptance home title');

        auth()->forgetGuards();

        visit('/')
            ->assertSee('Acceptance home title')
            ->assertDontSee(__('website.status.preview_draft'))
            ->assertMissing('[data-test="save_website_draft"]')
            ->assertScript("document.querySelector('meta[name=robots]').content", 'index,follow')
            ->assertNoJavaScriptErrors();

        actingAs($actor);

        visit('/website/content/home')
            ->type('#website_page_title', 'Acceptance home revised')
            ->click('@save_website_draft')
            ->assertSee(__('website.helper.draft_saved', ['version' => 2]))
            ->click('@request_publish_website')
            ->click('@confirm_publish_website');

        expect($record->fresh()->published['title'])->toBe('Acceptance home revised');

        auth()->forgetGuards();

        visit('/')
            ->assertSee('Acceptance home revised')
            ->assertDontSee('Acceptance home title')
            ->assertNoJavaScriptErrors();
    });

    it('applies preset switches from the admin area without changing published content', function () use ($enablePublicPages, $publishPage): void {
        $enablePublicPages();
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent, Permission::ConfigureWebsite);
        $publishPage($actor, 'home', 'Preset acceptance title');

        visit('/website')
            ->assertSee(__('website.card.preset.title'))
            ->click('@website_preset_editorial')
            ->click('@apply_website_preset')
            ->click('@confirm_apply_website_preset')
            ->assertNoJavaScriptErrors();

        expect(resolve(WebsiteSettings::class)->refresh()->preset)->toBe('editorial');

        auth()->forgetGuards();

        visit('/')->assertAttribute('[data-public-preset]', 'data-public-preset', 'editorial')
            ->assertSee('Preset acceptance title')
            ->assertNoJavaScriptErrors();

        actingAs($actor);

        visit('/website')
            ->click('@website_preset_corporate')
            ->click('@apply_website_preset')
            ->click('@confirm_apply_website_preset')
            ->assertNoJavaScriptErrors();

        expect(resolve(WebsiteSettings::class)->refresh()->preset)->toBe('corporate');

        auth()->forgetGuards();

        visit('/')->assertAttribute('[data-public-preset]', 'data-public-preset', 'corporate')
            ->assertSee('Preset acceptance title')
            ->assertNoJavaScriptErrors();
    });

    it('passes automated accessibility checks for public pages', function (string $preset, string $path, string $title) use ($enablePublicPages, $publishPage, $publishArticle): void {
        $enablePublicPages();
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $publishPage($actor, 'home', 'Accessible home');
        $publishPage($actor, 'about', 'Accessible about');
        $publishPage($actor, 'contact', 'Accessible contact');
        $publishPage($actor, 'article-index', 'Accessible articles');
        $publishArticle($actor, 'accessible-article', 'Accessible article');
        $settings = resolve(WebsiteSettings::class);
        $settings->preset = $preset;
        $settings->save();
        auth()->forgetGuards();

        visit($path)
            ->assertSee($title)
            ->assertNoAccessibilityIssues(3)
            ->assertNoBrokenImages()
            ->assertNoJavaScriptErrors()
            ->screenshot(filename: 'scope-eight-'.$preset.'-'.str_replace('/', '-', mb_trim($path, '/')).'-desktop')
            ->resize(390, 844)
            ->assertNoAccessibilityIssues(3)
            ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
            ->screenshot(filename: 'scope-eight-'.$preset.'-'.str_replace('/', '-', mb_trim($path, '/')).'-mobile');
    })->with((function (): array {
        $cases = [];
        foreach (['corporate', 'editorial', 'minimal'] as $preset) {
            foreach (['/' => 'Accessible home', '/about' => 'Accessible about', '/contact' => 'Accessible contact', '/articles' => 'Accessible articles', '/articles/accessible-article' => 'Accessible article'] as $path => $title) {
                $cases[$preset.' '.$path] = [$preset, $path, $title];
            }
        }

        return $cases;
    })());

    it('keeps keyboard focus, headings, labels, and image alternatives usable', function (int $width) use ($enablePublicPages, $publishPage, $publishArticle): void {
        $enablePublicPages();
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $publishPage($actor, 'contact', 'Accessible contact');
        $publishArticle($actor, 'accessible-article', 'Accessible article');

        $contact = visit('/contact')->resize($width, 844)->assertSee('Accessible contact')->assertNoJavaScriptErrors();
        expect($contact->script('[...document.querySelectorAll("form input, form textarea, form select")].every((field) => (field.id !== "" && document.querySelector(`label[for="${field.id}"]`) !== null) || field.getAttribute("aria-label") !== null || field.getAttribute("aria-labelledby") !== null)'))->toBeTrue()
            ->and($contact->script('[...document.querySelectorAll("form input:not([type=hidden]), form textarea")].every((field) => field.required || field.closest("[aria-hidden=true]") !== null)'))->toBeTrue();

        $article = visit('/articles/accessible-article')->resize($width, 844)->assertSee('Accessible article')->assertNoJavaScriptErrors();
        expect($article->script('document.querySelectorAll("h1").length'))->toBe(1)
            ->and($article->script('(() => { const levels = [...document.querySelectorAll("h1, h2, h3, h4, h5, h6")].map((heading) => Number(heading.tagName[1])); return levels.every((level, index) => index === 0 ? level === 1 : level <= levels[index - 1] + 1); })()'))->toBeTrue()
            ->and($article->script('[...document.querySelectorAll("main img")].every((image) => image.hasAttribute("alt"))'))->toBeTrue()
            ->and($article->script('document.querySelector("main img").alt'))->toBe('Acceptance cover alt');

        $home = visit('/')->resize($width, 844)->assertNoJavaScriptErrors();
        $home->withKeyDown('Tab', fn (): null => null);
        expect($home->script('document.activeElement.textContent.trim()'))->toBe(__('navigation.label.skip_content'));
        $home->keys('a[href="#public-content"]', 'Enter');
        expect($home->script('document.activeElement.id'))->toBe('public-content');

        $keyboard = visit('/')->resize($width, 844)->assertNoJavaScriptErrors();
        $keyboard->withKeyDown('Tab', fn (): null => null);
        $keyboard->withKeyDown('Tab', fn (): null => null);
        expect($keyboard->script('document.activeElement.matches("header a")'))->toBeTrue()
            ->and($keyboard->script('getComputedStyle(document.activeElement).outlineWidth !== "0px" && getComputedStyle(document.activeElement).outlineStyle !== "none"'))->toBeTrue();

        $keyboard->withKeyDown('Tab', fn (): null => null);
        expect($keyboard->script('document.activeElement.closest("nav") !== null'))->toBeTrue()
            ->and($keyboard->script('getComputedStyle(document.activeElement).outlineWidth !== "0px"'))->toBeTrue();
    })->with(['desktop' => 1440, 'mobile' => 390]);

    it('hides drafts and unpublished pages and redirects historical article slugs', function () use ($enablePublicPages, $publishPage): void {
        $enablePublicPages();
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $publishPage($actor, 'home', 'Visible home');
        resolve(SaveWebsiteDraft::class)->handle($actor, Arr::except(websiteDraft(['title' => 'Private about draft']), ['slug']), pageKey: 'about');

        $save = resolve(SaveWebsiteDraft::class);
        $publish = resolve(PublishWebsiteContent::class);
        $article = $save->handle($actor, websiteDraft(['title' => 'Acceptance article', 'slug' => 'acceptance-first']));
        $publish->handle($actor, $article, $article->draft_version);
        $article = $save->handle($actor, websiteDraft(['title' => 'Acceptance article', 'slug' => 'acceptance-second']), $article, $article->draft_version);
        $publish->handle($actor, $article, $article->draft_version);

        auth()->forgetGuards();

        visit('/')
            ->assertSee('Visible home')
            ->assertDontSee('Private about draft')
            ->assertNoJavaScriptErrors();

        visit('/about')
            ->assertSee(__('error_page.404.title'))
            ->assertDontSee('Private about draft')
            ->assertNoJavaScriptErrors();

        visit('/articles/acceptance-first')
            ->assertPathIs('/articles/acceptance-second')
            ->assertSee('Acceptance article')
            ->assertNoJavaScriptErrors();

        visit('/articles/acceptance-second')
            ->assertSee('Acceptance article')
            ->assertNoJavaScriptErrors();
    });

    it('keeps disabled public access behind authentication', function () use ($enablePublicPages, $publishPage): void {
        $enablePublicPages();
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $publishPage($actor, 'about', 'Disabled about');
        $general = resolve(GeneralSettings::class);
        $general->public_pages_enabled = false;
        $general->save();

        auth()->forgetGuards();

        visit('/about')
            ->assertSee(__('error_page.404.title'))
            ->assertDontSee('Disabled about')
            ->assertNoJavaScriptErrors();

        visit('/')
            ->assertPathIs('/login')
            ->assertNoJavaScriptErrors();
    });
});
