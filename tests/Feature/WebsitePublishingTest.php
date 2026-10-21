<?php

declare(strict_types=1);

use App\Actions\Website\GetPublicWebsiteContent;
use App\Actions\Website\GetPublicWebsiteSettings;
use App\Actions\Website\GetWebsiteImage;
use App\Actions\Website\PublishWebsiteContent;
use App\Actions\Website\SaveWebsiteDraft;
use App\Actions\Website\UnpublishWebsiteContent;
use App\Actions\Website\UpdateWebsiteSettings;
use App\Actions\Website\UploadWebsiteImage;
use App\Enums\Permission;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\WebsiteContent;
use App\Models\WebsiteSlug;
use App\Settings\GeneralSettings;
use App\Settings\WebsiteSettings;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertModelExists;

describe('Website publishing foundation', function (): void {
    it('keeps drafts private and explicitly publishes sanitized snapshots', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $content = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft());
        expect(resolve(GetPublicWebsiteContent::class)->handle('company-news', true))->toBeNull()
            ->and($content->toArray())->not->toHaveKey('draft');
        assertModelExists($content);

        $published = resolve(PublishWebsiteContent::class)->handle($actor, $content, 1);
        $result = resolve(GetPublicWebsiteContent::class)->handle('company-news', true);

        expect($result['content'])->toHaveKeys(['kind', 'title', 'slug', 'excerpt', 'body_html', 'body_text', 'sections', 'seo', 'cover', 'author', 'published_at'])
            ->not->toHaveKeys(['draft', 'body_json', 'author_id', 'updated_by', 'email'])
            ->toMatchArray(['body_html' => '<p>&lt;script&gt;example&lt;/script&gt;</p>', 'author' => $actor->name])
            ->and($published->publication_version)->toBeUuid();
        assertDatabaseHas('audit_logs', ['event' => 'website.published', 'subject_id' => $content->id]);
    });

    it('invalidates publication caches after commit and preserves them on rollback', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $content = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft());
        $published = resolve(PublishWebsiteContent::class)->handle($actor, $content, 1);
        resolve(GetPublicWebsiteContent::class)->handle('company-news', true);
        $oldKey = 'website.content.'.$content->id.'.'.$published->publication_version;
        Cache::put('website.sitemap', 'old sitemap');
        expect(Cache::has($oldKey))->toBeTrue();
        resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['title' => 'Updated']), $content, 1);

        expect(fn () => DB::transaction(function () use ($actor, $content): void {
            resolve(PublishWebsiteContent::class)->handle($actor, $content, 2);
            throw new RuntimeException('Rollback');
        }))->toThrow(RuntimeException::class, 'Rollback')
            ->and(Cache::get('website.sitemap'))->toBe('old sitemap')
            ->and(Cache::has($oldKey))->toBeTrue()
            ->and(resolve(GetPublicWebsiteContent::class)->handle('company-news', true)['content']['title'])->toBe('Company news');
        resolve(PublishWebsiteContent::class)->handle($actor, $content, 2);
        expect(Cache::has($oldKey))->toBeFalse()
            ->and(Cache::has('website.sitemap'))->toBeFalse()
            ->and(resolve(GetPublicWebsiteContent::class)->handle('company-news', true)['content']['title'])->toBe('Updated');
        $current = $content->fresh();
        $currentKey = 'website.content.'.$current->id.'.'.$current->publication_version;
        resolve(UnpublishWebsiteContent::class)->handle($actor, $content);
        expect(Cache::has($currentKey))->toBeFalse();
    });

    it('adds and rolls back CMS storage without changing existing application data', function (): void {
        $user = User::factory()->create();
        $general = resolve(GeneralSettings::class);
        $general->site_name = 'Existing organization';
        $general->save();
        $migration = require database_path('migrations/website/2026_10_17_045751_create_website_contents_table.php');
        $settingsMigration = require database_path('settings/website/2026_10_17_045750_create_website_settings.php');

        $migration->down();
        $settingsMigration->down();
        $migration->up();
        $settingsMigration->up();

        assertModelExists($user);
        expect(resolve(GeneralSettings::class)->refresh()->site_name)->toBe('Existing organization')
            ->and(resolve(WebsiteSettings::class)->refresh()->preset)->toBe('corporate');
        assertDatabaseCount('website_contents', 0);
    });

    it('retains the published snapshot and URL while editing the next draft', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $content = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft());
        resolve(PublishWebsiteContent::class)->handle($actor, $content, 1);
        $old = resolve(GetPublicWebsiteContent::class)->handle('company-news', true);

        $edited = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['title' => 'Private revision', 'slug' => 'new-url', 'seo' => ['title' => 'Private SEO']]), $content, 1);

        expect($edited->draft_version)->toBe(2)
            ->and(resolve(GetPublicWebsiteContent::class)->handle('company-news', true))->toBe($old)
            ->and(resolve(GetPublicWebsiteContent::class)->handle('new-url', true))->toBeNull();
        resolve(PublishWebsiteContent::class)->handle($actor, $edited, 2);
        expect(resolve(GetPublicWebsiteContent::class)->handle('company-news', true)['redirect_slug'])->toBe('new-url')
            ->and(resolve(GetPublicWebsiteContent::class)->handle('new-url', true)['content']['seo'])->toBe(['title' => 'Private SEO']);
    });

    it('supports each singleton page independently without overwriting existing pages', function (string $pageKey): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $data = websiteDraft();
        unset($data['slug']);
        $content = resolve(SaveWebsiteDraft::class)->handle($actor, $data, pageKey: $pageKey);
        resolve(PublishWebsiteContent::class)->handle($actor, $content, 1);

        expect(resolve(GetPublicWebsiteContent::class)->handle($pageKey)['content']['kind'])->toBe($pageKey)
            ->and(fn () => resolve(SaveWebsiteDraft::class)->handle($actor, $data, pageKey: $pageKey))->toThrow(ValidationException::class);
        assertDatabaseCount('website_contents', 1);
    })->with(['home', 'about', 'contact', 'article-index']);

    it('rejects stale saves and publishing without changing either revision', function (string $operation): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $content = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft());
        $updated = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['title' => 'Current draft']), $content, 1);

        expect(fn () => $operation === 'save'
            ? resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['title' => 'Stale overwrite']), $content, 1)
            : resolve(PublishWebsiteContent::class)->handle($actor, $content, 1))->toThrow(ValidationException::class)
            ->and($updated->fresh()->draft['title'])->toBe('Current draft')
            ->and($updated->fresh()->published)->toBeNull();
    })->with(['save', 'publish']);

    it('enforces separate edit and publish permissions in backend actions', function (): void {
        $editor = actingAsUserWithPermissions(Permission::EditWebsiteContent);
        $publisher = actingAsUserWithPermissions(Permission::PublishWebsiteContent);
        $content = resolve(SaveWebsiteDraft::class)->handle($editor, websiteDraft());

        expect(fn () => resolve(PublishWebsiteContent::class)->handle($editor, $content, 1))->toThrow(AuthorizationException::class)
            ->and(fn () => resolve(UnpublishWebsiteContent::class)->handle($editor, $content))->toThrow(AuthorizationException::class)
            ->and(fn () => resolve(SaveWebsiteDraft::class)->handle($publisher, websiteDraft()))->toThrow(AuthorizationException::class)
            ->and(fn () => resolve(SaveWebsiteDraft::class)->handle($publisher, websiteDraft(), $content, 1))->toThrow(AuthorizationException::class);

        resolve(PublishWebsiteContent::class)->handle($publisher, $content, 1);
        expect($content->fresh()->published)->not->toBeNull();
    });

    it('checks policy permissions for ordinary and disabled accounts', function (Permission $permission, string $ability): void {
        $content = WebsiteContent::factory()->create();
        $ordinary = User::factory()->create();
        $allowed = User::factory()->create();
        assignRolePermissions($allowed, $permission);
        $disabled = User::factory()->disabled()->create();
        assignRolePermissions($disabled, $permission);

        expect(Gate::forUser($ordinary)->allows($ability, $content))->toBeFalse()
            ->and(Gate::forUser($allowed)->allows($ability, $content))->toBeTrue()
            ->and(Gate::forUser($disabled)->allows($ability, $content))->toBeFalse();
    })->with([
        'edit' => [Permission::EditWebsiteContent, 'update'],
        'publish' => [Permission::PublishWebsiteContent, 'publish'],
        'editor preview' => [Permission::EditWebsiteContent, 'preview'],
        'publisher preview' => [Permission::PublishWebsiteContent, 'preview'],
    ]);

    it('hides future publications and disabled websites, even after caching', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $content = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft());
        resolve(PublishWebsiteContent::class)->handle($actor, $content, 1, now()->addDay()->toIso8601String());
        expect(resolve(GetPublicWebsiteContent::class)->handle('company-news', true))->toBeNull();
        resolve(PublishWebsiteContent::class)->handle($actor, $content, 1);
        expect(resolve(GetPublicWebsiteContent::class)->handle('company-news', true))->not->toBeNull();
        $general = resolve(GeneralSettings::class);
        resolve(WebsiteSettings::class)->fill(['public_pages_enabled' => false])->save();
        $general->save();

        expect(resolve(GetPublicWebsiteContent::class)->handle('company-news', true))->toBeNull()
            ->and(resolve(GetPublicWebsiteSettings::class)->handle())->toBeNull();
    });

    it('retains historical slugs and never redirects to an unpublished article', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $content = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft());
        resolve(PublishWebsiteContent::class)->handle($actor, $content, 1);
        $edited = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['slug' => 'next-slug']), $content, 1);
        resolve(PublishWebsiteContent::class)->handle($actor, $edited, 2);
        expect(fn () => resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft()))->toThrow(ValidationException::class);
        assertDatabaseCount('website_contents', 1);
        expect(resolve(GetPublicWebsiteContent::class)->handle('company-news', true)['redirect_slug'])->toBe('next-slug');

        resolve(UnpublishWebsiteContent::class)->handle($actor, $content);

        expect(resolve(GetPublicWebsiteContent::class)->handle('company-news', true))->toBeNull()
            ->and(resolve(GetPublicWebsiteContent::class)->handle('next-slug', true))->toBeNull()
            ->and(WebsiteSlug::query()->where('was_published', true)->count())->toBe(2)
            ->and($content->fresh()->draft['slug'])->toBe('next-slug');
    });

    it('allows returning to a historical slug without a redirect loop and releases unused draft slugs', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $content = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft());
        resolve(PublishWebsiteContent::class)->handle($actor, $content, 1);
        resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['slug' => 'unused-slug']), $content, 1);
        resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(), $content, 2);
        resolve(PublishWebsiteContent::class)->handle($actor, $content, 3);

        expect(resolve(GetPublicWebsiteContent::class)->handle('company-news', true)['redirect_slug'])->toBeNull()
            ->and(WebsiteSlug::query()->where('slug', 'unused-slug')->exists())->toBeFalse();
    });

    it('rejects invalid content and injected publication fields without persisting changes', function (array $overrides): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent);

        expect(fn () => resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft($overrides)))->toThrow(ValidationException::class);

        assertDatabaseCount('website_contents', 0);
        assertDatabaseCount('website_slugs', 0);
    })->with([
        'empty title' => [['title' => '']],
        'unsafe slug' => [['slug' => '../admin']],
        'unknown fields' => [['published' => ['title' => 'Injected']]],
        'raw HTML' => [['body_json' => ['type' => 'script']]],
        'unsafe rich text link' => [['body_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'link', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]]]]]]]]],
        'SEO injection' => [['seo' => ['canonical' => 'https://attacker.test']]],
        'unknown section' => [['sections' => [['id' => 'x', 'type' => 'html', 'visible' => true, 'title' => 'Unsafe']]]],
        'non-list order' => [['sections' => ['custom' => ['id' => 'x', 'type' => 'articles', 'visible' => true, 'title' => 'Articles', 'featured' => false, 'limit' => 3]]]],
        'unbounded articles' => [['sections' => [['id' => 'x', 'type' => 'articles', 'visible' => true, 'title' => 'Articles', 'featured' => false, 'limit' => 100]]]],
        'extra section field' => [['sections' => [['id' => 'x', 'type' => 'articles', 'visible' => true, 'title' => 'Articles', 'featured' => false, 'limit' => 3, 'html' => '<script/>']]]],
        'unsafe CTA' => [['sections' => [['id' => 'x', 'type' => 'contact', 'visible' => true, 'title' => 'Contact', 'description' => '', 'cta' => ['label' => 'Click', 'href' => '//attacker.test']]]]],
        'missing cover' => [['cover' => ['media_id' => 99999, 'alt' => 'Image']]],
    ]);

    it('renders structured sections safely and preserves their explicit order', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $sections = [
            ['id' => 'intro', 'type' => 'introduction', 'visible' => true, 'title' => 'Introduction', 'body_json' => websiteDraft()['body_json']],
            ['id' => 'hero', 'type' => 'hero', 'visible' => true, 'title' => 'Home', 'description' => 'Hello', 'image' => null, 'cta' => ['label' => 'Contact', 'href' => '/contact']],
            ['id' => 'services', 'type' => 'services', 'visible' => true, 'title' => 'Services', 'items' => [['title' => 'Support', 'description' => 'Contact support']]],
            ['id' => 'strengths', 'type' => 'strengths', 'visible' => true, 'title' => 'Strengths', 'items' => [['title' => 'Care', 'description' => 'Attention']]],
            ['id' => 'articles', 'type' => 'articles', 'visible' => '0', 'title' => 'Articles', 'featured' => '1', 'limit' => '3'],
            ['id' => 'contact', 'type' => 'contact', 'visible' => true, 'title' => 'Contact', 'description' => '', 'cta' => ['label' => 'Email', 'href' => 'mailto:hello@example.test']],
        ];
        $content = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['sections' => $sections]));
        resolve(PublishWebsiteContent::class)->handle($actor, $content, 1);
        $public = resolve(GetPublicWebsiteContent::class)->handle('company-news', true)['content'];

        expect(array_column($public['sections'], 'id'))->toBe(['intro', 'hero', 'services', 'strengths', 'articles', 'contact'])
            ->and($public['sections'][0]['body'])->toBe('<p>&lt;script&gt;example&lt;/script&gt;</p>')
            ->and($public['sections'][0])->not->toHaveKey('body_json');
    });

    it('rejects duplicate section identifiers', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent);
        $section = ['id' => 'duplicate', 'type' => 'articles', 'visible' => true, 'title' => 'Articles', 'featured' => false, 'limit' => 3];

        expect(fn () => resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['sections' => [$section, $section]])))->toThrow(ValidationException::class);
    });

    it('rolls back the snapshot, slug history and audit when publication fails', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $content = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft());
        Event::listen('eloquent.creating: '.AuditLog::class, static function (AuditLog $log): void {
            throw_if($log->event === 'website.published', RuntimeException::class, 'Audit unavailable');
        });

        expect(fn () => resolve(PublishWebsiteContent::class)->handle($actor, $content, 1))->toThrow(RuntimeException::class, 'Audit unavailable')
            ->and($content->fresh()->published)->toBeNull()
            ->and(WebsiteSlug::query()->sole()->was_published)->toBeFalse()
            ->and(AuditLog::query()->where('event', 'website.published')->exists())->toBeFalse();
    });
});

describe('Website settings and private media', function (): void {
    it('starts with non-fabricated defaults without altering general settings', function (): void {
        $general = resolve(GeneralSettings::class)->toArray();
        $settings = resolve(WebsiteSettings::class);

        expect($settings->preset)->toBe('corporate')
            ->and($settings->identity)->toBeEmpty()
            ->and($settings->navigation)->toBeEmpty()
            ->and($settings->contact['email'])->toBeNull()
            ->and(resolve(GeneralSettings::class)->toArray())->toBe($general);
    });

    it('keeps preset preferences and content when switching away and back', function (): void {
        $actor = actingAsUserWithPermissions(Permission::ConfigureWebsite);
        $content = WebsiteContent::factory()->create();
        $draft = $content->draft;
        resolve(UpdateWebsiteSettings::class)->handle($actor, ['preferences' => ['corporate' => ['sectionOrder' => ['intro', 'hero'], 'hiddenSections' => ['hero']]]]);
        $before = resolve(GetPublicWebsiteSettings::class)->handle();
        resolve(UpdateWebsiteSettings::class)->handle($actor, ['preset' => 'editorial', 'preferences' => ['editorial' => ['sectionOrder' => ['hero'], 'hiddenSections' => []]]]);
        resolve(UpdateWebsiteSettings::class)->handle($actor, ['preset' => 'corporate', 'seo' => ['title' => 'Organization', 'description' => 'Contact information', 'robots' => 'index,follow']]);
        $after = resolve(GetPublicWebsiteSettings::class)->handle();

        expect($after['preferences']['corporate'])->toBe($before['preferences']['corporate'])
            ->and($after['preferences']['editorial']['sectionOrder'])->toBe(['hero'])
            ->and($after['seo']['title'])->toBe('Organization')
            ->and($after)->not->toHaveKey('public_version')
            ->and($content->fresh()->draft)->toEqual($draft);
    });

    it('invalidates settings and sitemap caches when preset or SEO changes', function (): void {
        $actor = actingAsUserWithPermissions(Permission::ConfigureWebsite);
        $oldVersion = resolve(WebsiteSettings::class)->public_version;
        resolve(GetPublicWebsiteSettings::class)->handle();
        Cache::put('website.sitemap', 'old sitemap');

        resolve(UpdateWebsiteSettings::class)->handle($actor, ['preset' => 'minimal']);

        expect(Cache::has('website.settings.'.$oldVersion))->toBeFalse()
            ->and(Cache::has('website.sitemap'))->toBeFalse()
            ->and(resolve(GetPublicWebsiteSettings::class)->handle()['preset'])->toBe('minimal');
    });

    it('refuses configuration from editors and inbox readers', function (Permission $permission): void {
        $actor = actingAsUserWithPermissions($permission);

        expect(fn () => resolve(UpdateWebsiteSettings::class)->handle($actor, ['preset' => 'minimal']))->toThrow(AuthorizationException::class)
            ->and(resolve(WebsiteSettings::class)->preset)->toBe('corporate');
    })->with([Permission::EditWebsiteContent, Permission::PublishWebsiteContent, Permission::ViewContactInbox]);

    it('rejects unsafe settings without partial updates', function (array $input): void {
        $actor = actingAsUserWithPermissions(Permission::ConfigureWebsite);

        expect(fn () => resolve(UpdateWebsiteSettings::class)->handle($actor, $input))->toThrow(ValidationException::class)
            ->and(resolve(WebsiteSettings::class)->preset)->toBe('corporate');
    })->with([
        'unknown preset' => [['preset' => 'custom']],
        'unsafe navigation' => [['preset' => 'minimal', 'navigation' => [['label' => 'Unsafe', 'href' => 'javascript:alert(1)']]]],
        'extra navigation field' => [['navigation' => [['label' => 'Home', 'href' => '/', 'html' => '<b>Home</b>']]]],
        'arbitrary preferences' => [['preferences' => ['unknown' => ['sectionOrder' => [], 'hiddenSections' => []]]]],
        'invalid order' => [['preferences' => ['corporate' => ['sectionOrder' => ['intro', 'intro'], 'hiddenSections' => []]]]],
        'invalid email' => [['contact' => ['email' => 'bad']]],
        'arbitrary setting' => [['public_version' => 'spoofed']],
    ]);

    it('keeps media private until explicitly referenced by a published snapshot', function (): void {
        Storage::fake('local');
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $content = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft());
        $media = resolve(UploadWebsiteImage::class)->handle($actor, $content, UploadedFile::fake()->image('cover.png', 100, 100));
        expect($media->disk)->toBe('local');
        Storage::disk('local')->assertExists($media->getPathRelativeToRoot());
        expect(resolve(GetWebsiteImage::class)->handle($media->id))->toBeNull()
            ->and(resolve(GetWebsiteImage::class)->handle($media->id, $actor)?->id)->toBe($media->id);
        $edited = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['cover' => ['media_id' => (string) $media->id, 'alt' => 'Company office']]), $content, 1);
        resolve(PublishWebsiteContent::class)->handle($actor, $edited, 2);
        expect(resolve(GetWebsiteImage::class)->handle($media->id)?->id)->toBe($media->id);
        $next = resolve(UploadWebsiteImage::class)->handle($actor, $content, UploadedFile::fake()->image('next.png'));
        resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['cover' => ['media_id' => $next->id, 'alt' => 'Next cover']]), $content, 2);
        expect(resolve(GetWebsiteImage::class)->handle($next->id))->toBeNull()
            ->and(resolve(GetWebsiteImage::class)->handle($media->id)?->id)->toBe($media->id);
        resolve(UnpublishWebsiteContent::class)->handle($actor, $content);
        expect(resolve(GetWebsiteImage::class)->handle($media->id))->toBeNull();
    });

    it('rejects foreign media references and unsafe uploads', function (): void {
        Storage::fake('local');
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent);
        $content = WebsiteContent::factory()->create();
        $other = WebsiteContent::factory()->create();
        $media = resolve(UploadWebsiteImage::class)->handle($actor, $other, UploadedFile::fake()->image('other.png'));

        expect(fn () => resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['cover' => ['media_id' => $media->id, 'alt' => 'Other image']]), $content, 1))->toThrow(ValidationException::class)
            ->and(fn () => resolve(UploadWebsiteImage::class)->handle($actor, $content, UploadedFile::fake()->create('script.svg', 1, 'image/svg+xml')))->toThrow(ValidationException::class)
            ->and(resolve(GetWebsiteImage::class)->handle($media->id, User::factory()->create()))->toBeNull();
        assertDatabaseCount('media', 1);
    });

    it('refuses media uploads from unauthorized accounts', function (): void {
        $actor = User::factory()->create();
        $content = WebsiteContent::factory()->create();

        expect(fn () => resolve(UploadWebsiteImage::class)->handle($actor, $content, UploadedFile::fake()->image('cover.png')))->toThrow(AuthorizationException::class);
        assertDatabaseCount('media', 0);
    });
});
