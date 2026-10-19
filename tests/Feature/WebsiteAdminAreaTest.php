<?php

declare(strict_types=1);

use App\Actions\Website\GetPublicWebsiteContent;
use App\Actions\Website\GetPublicWebsiteSettings;
use App\Actions\Website\InvalidateWebsiteCache;
use App\Actions\Website\PublishWebsiteContent;
use App\Actions\Website\SaveWebsiteDraft;
use App\Enums\Permission;
use App\Models\User;
use App\Models\WebsiteContent;
use App\Settings\WebsiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

/**
 * A valid structured page payload for HTTP submissions.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function websitePageDraft(string $page = 'home', array $overrides = []): array
{
    $data = [
        'title' => 'Home draft',
        'excerpt' => 'A short summary',
        'body_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Hello']]]]],
        'sections' => [],
        'seo' => ['title' => '', 'description' => '', 'robots' => 'index,follow', 'image' => null],
        'cover' => null,
    ];

    return array_replace($data, $overrides);
}
/**
 * A recorded singleton page draft owned by the given user.
 */
function savedHomeDraft(User $owner): WebsiteContent
{
    return resolve(SaveWebsiteDraft::class)->handle($owner, websitePageDraft(), pageKey: 'home');
}

describe('Website admin area', function (): void {
    it('saves an empty optional page summary from the editor', function (): void {
        actingAsUserWithPermissions(Permission::EditWebsiteContent);

        post('/website/content/home/draft', [...websitePageDraft(overrides: ['excerpt' => '']), 'version' => 0])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        expect(WebsiteContent::query()->where('page_key', 'home')->sole()->draft['excerpt'])->toBeNull();
    });

    it('requires authentication for every admin endpoint', function (string $method, string $uri): void {
        $response = match ($method) {
            'GET' => get($uri),
            'POST' => post($uri),
            default => patch($uri),
        };

        $response->assertRedirect(route('login', absolute: false));
    })->with([
        'index' => ['GET', '/website'],
        'page editor' => ['GET', '/website/content/home'],
        'draft save' => ['POST', '/website/content/home/draft'],
        'publish' => ['POST', '/website/content/home/publish'],
        'image upload' => ['POST', '/website/content/home/image'],
        'preview' => ['GET', '/website/preview/home'],
        'preview image' => ['GET', '/website/preview/images/1'],
        'settings edit' => ['GET', '/website/settings'],
        'settings update' => ['PATCH', '/website/settings'],
        'preset update' => ['PATCH', '/website/preset'],
    ]);

    it('scopes the website hub to editors, publishers and configurators', function (Permission $permission): void {
        actingAsUserWithPermissions($permission);

        get('/website')->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->component('website/Index')
            ->where('area.preset', resolve(WebsiteSettings::class)->preset)
            ->where('area.pages.0.page', 'home')
            ->where('area.pages.0.status', null));
    })->with([
        'editor' => [Permission::EditWebsiteContent],
        'publisher' => [Permission::PublishWebsiteContent],
        'configure' => [Permission::ConfigureWebsite],
    ]);

    it('routes inbox operators away from content management', function (): void {
        actingAsUserWithPermissions(Permission::ViewContactInbox);

        get('/website')->assertRedirect(route('website.inbox.index'));
        get('/website/settings')->assertForbidden();
        get('/website/preview/home')->assertForbidden();
    });

    it('opens page editors for editors and publishers but not configurators', function (Permission $permission, int $status): void {
        actingAsUserWithPermissions($permission);

        $response = get('/website/content/home')->assertStatus($status);
        if ($status === 200) {
            $response->assertInertia(fn (Assert $page): Assert => $page
                ->component('website/Content')
                ->where('editor.page.page', 'home')
                ->where('editor.content', null)
                ->where('editor.status', null));
        }
    })->with([
        'editor' => [Permission::EditWebsiteContent, 200],
        'publisher' => [Permission::PublishWebsiteContent, 200],
        'configure' => [Permission::ConfigureWebsite, 403],
    ]);

    it('gives configurators their own settings area instead of page editors', function (Permission $permission, bool $allowed): void {
        actingAsUserWithPermissions($permission);

        $response = get('/website/settings');
        $allowed
            ? $response->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('website/Settings'))
            : $response->assertForbidden();
    })->with([
        'editor' => [Permission::EditWebsiteContent, false],
        'publisher' => [Permission::PublishWebsiteContent, false],
        'configure' => [Permission::ConfigureWebsite, true],
    ]);

    it('rejects every mutation from roles lacking the endpoint permission', function (Permission $permission, string $method, string $uri): void {
        $author = actingAsUserWithPermissions(Permission::EditWebsiteContent);
        savedHomeDraft($author);
        actingAsUserWithPermissions($permission);

        $response = $method === 'POST' ? post($uri, []) : patch($uri, []);

        $response->assertForbidden();
    })->with([
        'draft save by publisher' => [Permission::PublishWebsiteContent, 'POST', '/website/content/home/draft'],
        'image upload by publisher' => [Permission::PublishWebsiteContent, 'POST', '/website/content/home/image'],
        'publish by editor' => [Permission::EditWebsiteContent, 'POST', '/website/content/home/publish'],
        'unpublish by editor' => [Permission::EditWebsiteContent, 'POST', '/website/content/home/unpublish'],
        'settings update by publisher' => [Permission::PublishWebsiteContent, 'PATCH', '/website/settings'],
        'settings update by editor' => [Permission::EditWebsiteContent, 'PATCH', '/website/settings'],
        'preset update by editor' => [Permission::EditWebsiteContent, 'PATCH', '/website/preset'],
        'preset update by publisher' => [Permission::PublishWebsiteContent, 'PATCH', '/website/preset'],
        'draft save by configurator' => [Permission::ConfigureWebsite, 'POST', '/website/content/home/draft'],
        'image upload by configurator' => [Permission::ConfigureWebsite, 'POST', '/website/content/home/image'],
    ]);

    it('saves page drafts over http and reports invalid data on clean keys', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent);

        $invalid = websitePageDraft('home', [
            'version' => 0,
            'sections' => [
                ['id' => 'hero', 'type' => 'hero', 'visible' => true, 'title' => '', 'description' => '', 'cta' => ['label' => '', 'href' => 'javascript:alert(1)']],
                ['id' => 'services', 'type' => 'services', 'visible' => true, 'title' => 'Services', 'items' => [['title' => 'Only title']]],
            ],
        ]);

        actingAs($actor)->post('/website/content/home/draft', $invalid)
            ->assertInvalid(['sections.0.title', 'sections.0.cta.label', 'sections.0.cta.href', 'sections.1.items.0.description']);

        $duplicate = $invalid;
        $duplicate['sections'][1]['id'] = 'hero';
        actingAs($actor)->post('/website/content/home/draft', $duplicate)
            ->assertInvalid(['sections']);

        expect(WebsiteContent::query()->count())->toBe(0);

        actingAs($actor)->post('/website/content/home/draft', websitePageDraft('home', ['version' => 0]))
            ->assertRedirect(route('website.page.edit', ['page' => 'home'], absolute: false));

        $record = WebsiteContent::query()->where('page_key', 'home')->firstOrFail();
        expect($record->draft_version)->toBe(1)
            ->and($record->draft['title'])->toBe('Home draft');
        assertDatabaseHas('audit_logs', ['event' => 'website.draft.saved', 'subject_id' => $record->id]);
    });

    it('rejects stale page drafts without overwriting the current revision', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent);
        $record = savedHomeDraft($actor);

        actingAs($actor)->post('/website/content/home/draft', websitePageDraft('home', [
            'title' => 'Concurrent edit',
            'version' => $record->draft_version + 3,
        ]))->assertInvalid(['version']);

        $record->refresh();
        expect($record->draft['title'])->toBe('Home draft')
            ->and($record->draft_version)->toBe(1);
    });

    it('publishes and withdraws the home draft while the public route stays intact', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $record = savedHomeDraft($actor);

        actingAs($actor)->post('/website/content/home/publish')
            ->assertRedirect(route('website.page.edit', ['page' => 'home'], absolute: false));
        expect(resolve(GetPublicWebsiteContent::class)->handle('home')['content']['title'])->toBe('Home draft');

        resolve(SaveWebsiteDraft::class)->handle($actor, websitePageDraft('home', ['title' => 'Private revision']), $record, 1);

        expect(resolve(GetPublicWebsiteContent::class)->handle('home')['content']['title'])->toBe('Home draft');

        actingAs($actor)->post('/website/content/home/publish')->assertRedirect();
        expect(resolve(GetPublicWebsiteContent::class)->handle('home')['content']['title'])->toBe('Private revision');

        actingAs($actor)->post('/website/content/home/unpublish')->assertRedirect();
        expect(resolve(GetPublicWebsiteContent::class)->handle('home'))->toBeNull()
            ->and($record->refresh()->published)->toBeNull();
    });

    it('shows working drafts in previews only to editors and publishers', function (Permission $permission, string $mode, string $expectedTitle): void {
        $editor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $record = savedHomeDraft($editor);
        resolve(PublishWebsiteContent::class)->handle($editor, $record, 1);
        resolve(SaveWebsiteDraft::class)->handle($editor, websitePageDraft('home', ['title' => 'Private revision']), $record, 1);
        resolve(InvalidateWebsiteCache::class)->handle();

        actingAsUserWithPermissions($permission);

        get('/website/preview/home')->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->component('public/Preview')
            ->where('website.preview.mode', $mode)
            ->where('website.page.title', $expectedTitle));
    })->with([
        'editor sees the working draft' => [Permission::EditWebsiteContent, 'draft', 'Private revision'],
        'publisher sees the working draft' => [Permission::PublishWebsiteContent, 'draft', 'Private revision'],
        'configurator sees the published snapshot' => [Permission::ConfigureWebsite, 'published', 'Home draft'],
    ]);

    it('previews unpublished pages as empty for configurators', function (): void {
        $editor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        savedHomeDraft($editor);

        actingAsUserWithPermissions(Permission::ConfigureWebsite);

        get('/website/preview/home')->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->component('public/Preview')
            ->where('website.preview.mode', 'empty')
            ->where('website.page.sections.0.id', 'public-introduction'));
    });

    it('uploads page images after the first draft save and gates previews', function (): void {
        Storage::fake('local');
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent);
        savedHomeDraft($actor);

        actingAs($actor)->post('/website/content/home/draft', websitePageDraft())->assertRedirect();
        actingAs($actor)->post('/website/content/home/image', [
            'image' => UploadedFile::fake()->image('plug.png', 120, 120),
        ])->assertRedirect();

        $record = WebsiteContent::query()->where('page_key', 'home')->firstOrFail();
        $media = $record->getMedia('website-images');
        expect($media)->toHaveCount(1);
        $mediaId = (int) $media->first()->id;

        get('/website/preview/images/'.$mediaId)->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        actingAsUserWithPermissions(Permission::ConfigureWebsite);
        get('/website/preview/images/'.$mediaId)->assertOk();

        actingAsUserWithPermissions(Permission::ViewContactInbox);
        get('/website/preview/images/'.$mediaId)->assertNotFound();

        actingAs($actor)->post('/website/content/home/image', [
            'image' => UploadedFile::fake()->create('plug.pdf', 20, 'application/pdf'),
        ])->assertInvalid(['image']);
    });

    it('applies preset switches without changing published content', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $record = savedHomeDraft($actor);
        resolve(PublishWebsiteContent::class)->handle($actor, $record, 1);
        expect(resolve(GetPublicWebsiteContent::class)->handle('home'))->not->toBeNull();

        actingAsUserWithPermissions(Permission::ConfigureWebsite);

        patch('/website/preset', ['preset' => 'minimal'])
            ->assertRedirect(route('website.index', absolute: false));
        expect(resolve(WebsiteSettings::class)->refresh()->preset)->toBe('minimal')
            ->and(resolve(GetPublicWebsiteContent::class)->handle('home')['content']['title'])->toBe('Home draft');

        patch('/website/preset', ['preset' => 'corporate'])->assertRedirect();
        expect(resolve(WebsiteSettings::class)->refresh()->preset)->toBe('corporate');

        patch('/website/preset', ['preset' => 'custom'])->assertInvalid(['preset']);
        expect(resolve(WebsiteSettings::class)->refresh()->preset)->toBe('corporate');
    });

    it('stores website settings and keeps them out of other users hands', function (): void {
        $actor = actingAsUserWithPermissions(Permission::ConfigureWebsite);

        patch('/website/settings', [
            'identity' => 'Jaya Committee',
            'seo' => ['title' => 'Default title', 'description' => 'Default description', 'robots' => 'noindex,nofollow'],
            'navigation' => [
                ['label' => 'Home', 'href' => '/'],
                ['label' => 'About', 'href' => '/about'],
            ],
            'footer' => [
                'text' => 'Footer line',
                'links' => [['label' => 'Privacy', 'href' => 'https://example.com/privacy']],
            ],
            'contact' => [
                'email' => 'info@example.com',
                'phone' => '+1 555 0100',
                'address' => 'Main street 1',
                'hours' => 'Mo-Fr 9-17',
                'location_url' => 'https://example.com/map',
                'social_links' => [['label' => 'GitHub', 'href' => 'https://github.com/example']],
            ],
        ])->assertRedirect(route('website.settings.edit', absolute: false));

        $settings = resolve(WebsiteSettings::class);
        $settings->refresh();
        $public = resolve(GetPublicWebsiteSettings::class)->handle();

        expect($settings->identity)->toBe('Jaya Committee')
            ->and($settings->contact['social_links'][0]['href'])->toBe('https://github.com/example')
            ->and($public['navigation'][1]['href'])->toBe('/about')
            ->and($public['footer']['text'])->toBe('Footer line')
            ->and($public['contact']['email'])->toBe('info@example.com')
            ->and($public['seo']['robots'])->toBe('noindex,nofollow');
        assertDatabaseHas('audit_logs', ['event' => 'website.settings.updated']);
    });

    it('reports invalid website settings without partial updates', function (array $payload, string $key): void {
        actingAsUserWithPermissions(Permission::ConfigureWebsite);

        patch('/website/settings', $payload)->assertInvalid([$key]);

        expect(resolve(WebsiteSettings::class)->refresh()->identity)->toBe('');
    })->with([
        'unsafe navigation href' => fn (): array => [
            ['navigation' => [['label' => 'Unsafe', 'href' => 'javascript:alert(1)']]], 'navigation.0.href',
        ],
        'unknown navigation field' => fn (): array => [
            ['navigation' => [['label' => 'Home', 'href' => '/', 'html' => '<b>Home</b>']]], 'navigation.0',
        ],
        'invalid contact email' => fn (): array => [
            ['contact' => ['email' => 'not-an-email', 'phone' => '', 'address' => '', 'hours' => '', 'location_url' => '', 'social_links' => []]], 'contact.email',
        ],
        'unsafe social link' => fn (): array => [
            ['contact' => ['email' => '', 'phone' => '', 'address' => '', 'hours' => '', 'location_url' => '', 'social_links' => [['label' => 'X', 'href' => 'data:text/html,test']]]], 'contact.social_links.0.href',
        ],
    ]);

    it('audits configuration changes with actor identity', function (): void {
        $actor = actingAsUserWithPermissions(Permission::ConfigureWebsite);

        patch('/website/preset', ['preset' => 'editorial'])->assertRedirect();

        assertDatabaseHas('audit_logs', [
            'event' => 'website.settings.updated',
            'actor_id' => $actor->id,
        ]);
    });
});
