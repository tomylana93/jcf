<?php

declare(strict_types=1);

use App\Actions\Website\GetPublicArticles;
use App\Actions\Website\PublishWebsiteContent;
use App\Actions\Website\SaveWebsiteDraft;
use App\Enums\Permission;
use App\Models\User;
use App\Models\WebsiteContent;
use App\Settings\GeneralSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

/** @param array<string, mixed> $overrides
 * @return array<string, mixed>
 */
function articleInput(User $author, array $overrides = []): array
{
    return [...websiteDraft(), 'author_id' => $author->id, 'featured' => false, 'version' => 0, ...$overrides];
}

describe('Article administration and public reading', function (): void {
    it('requires authentication on all article admin routes', function (string $method, string $suffix): void {
        $uri = '/website/articles'.$suffix;
        ($method === 'GET' ? get($uri) : post($uri))->assertRedirect(route('login', absolute: false));
    })->with([['GET', ''], ['GET', '/create'], ['POST', ''], ['GET', '/00000000-0000-0000-0000-000000000001/edit'], ['GET', '/00000000-0000-0000-0000-000000000001/preview'], ['POST', '/00000000-0000-0000-0000-000000000001/draft'], ['POST', '/00000000-0000-0000-0000-000000000001/publish'], ['POST', '/00000000-0000-0000-0000-000000000001/unpublish'], ['POST', '/00000000-0000-0000-0000-000000000001/image']]);

    it('enforces editing publishing and preview permission boundaries', function (): void {
        $editor = actingAsUserWithPermissions(Permission::EditWebsiteContent);
        $article = resolve(SaveWebsiteDraft::class)->handle($editor, websiteDraft());
        $base = '/website/articles/'.$article->id;
        get('/website/articles/create')->assertOk();
        post($base.'/publish', ['version' => 1])->assertForbidden();
        post($base.'/unpublish')->assertForbidden();
        $publisher = actingAsUserWithPermissions(Permission::PublishWebsiteContent);
        get($base.'/edit')->assertOk();
        post($base.'/draft', articleInput($publisher))->assertForbidden();
        post($base.'/image')->assertForbidden();
        post('/website/articles', articleInput($publisher))->assertForbidden();
        get('/website/articles/create')->assertForbidden();
        actingAsUserWithPermissions(Permission::ConfigureWebsite);
        get($base.'/edit')->assertForbidden();
        get($base.'/preview')->assertForbidden();
        actingAsUserWithPermissions(Permission::ViewContactInbox);
        get('/website/articles')->assertForbidden();
    });

    it('saves previews publishes edits redirects and withdraws an article', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $author = User::factory()->create(['name' => 'Approved author']);
        post('/website/articles', articleInput($author, ['featured' => true]))->assertSessionHasNoErrors();
        $article = WebsiteContent::query()->whereNull('page_key')->sole();
        $base = '/website/articles/'.$article->id;
        get('/articles/company-news')->assertNotFound();
        get($base.'/preview')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertInertia(fn (Assert $page): Assert => $page->component('public/Preview')->where('website.page.article.author', 'Approved author')->where('website.preview.mode', 'draft')->where('website.page.seo.robots', 'noindex,nofollow'));
        post($base.'/publish', ['version' => 1])->assertSessionHasNoErrors();
        get('/articles/company-news')->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('public/Articles')->where('website.page.body', '<p>&lt;script&gt;example&lt;/script&gt;</p>')->where('website.page.article.author', 'Approved author')->missing('website.page.body_json')->missing('website.page.author_id'));
        post($base.'/draft', articleInput($actor, ['version' => 1, 'title' => 'Private new title', 'slug' => 'new-url']))->assertSessionHasNoErrors();
        get('/articles/company-news')->assertInertia(fn (Assert $page): Assert => $page->where('website.page.title', 'Company news')->where('website.page.article.author', 'Approved author'));
        get('/articles/new-url')->assertNotFound();
        post($base.'/publish', ['version' => 1])->assertSessionHasErrors('version');
        post($base.'/publish', ['version' => 2])->assertSessionHasNoErrors();
        get('/articles/company-news')->assertStatus(301)->assertRedirect(route('articles.show', ['slug' => 'new-url']));
        get('/articles/new-url')->assertOk();
        post($base.'/unpublish')->assertSessionHasNoErrors();
        get('/articles/company-news')->assertNotFound();
        get('/articles/new-url')->assertNotFound();
    });

    it('rejects invalid slugs collisions authors content and future publication dates', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        post('/website/articles', articleInput($actor))->assertSessionHasNoErrors();
        $article = WebsiteContent::query()->whereNull('page_key')->sole();
        post('/website/articles', articleInput($actor))->assertSessionHasErrors('slug');
        post('/website/articles', articleInput($actor, ['slug' => '../invalid']))->assertSessionHasErrors('slug');
        post('/website/articles', articleInput($actor, ['author_id' => '00000000-0000-0000-0000-000000000001']))->assertSessionHasErrors('author_id');
        post('/website/articles', articleInput($actor, ['body_json' => ['type' => 'script']]))->assertSessionHasErrors();
        post('/website/articles/'.$article->id.'/publish', ['version' => 1, 'published_at' => now()->addDay()->toIso8601String()])->assertSessionHasErrors('published_at');
        expect($article->fresh()->published)->toBeNull();
    });

    it('never binds singleton pages as articles', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $content = resolve(SaveWebsiteDraft::class)->handle($actor, Arr::except(websiteDraft(), 'slug'), pageKey: 'home');
        $base = '/website/articles/'.$content->id;
        get($base.'/edit')->assertNotFound();
        get($base.'/preview')->assertNotFound();
        foreach (['draft', 'publish', 'unpublish', 'image'] as $action) {
            post($base.'/'.$action, articleInput($actor, ['version' => 1]))->assertNotFound();
        }
    });

    it('paginates only public snapshots with bounded constant query counts', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        for ($n = 0; $n < 14; $n++) {
            $article = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['slug' => 'news-'.$n, 'title' => 'Public '.$n]));
            resolve(PublishWebsiteContent::class)->handle($actor, $article, 1);
        }
        resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['slug' => 'secret-draft', 'title' => 'Secret draft']));
        $future = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['slug' => 'future', 'title' => 'Future private']));
        resolve(PublishWebsiteContent::class)->handle($actor, $future, 1, now()->addDay()->toIso8601String());
        get('/articles?page=1&per_page=1000')->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('website.articles', 12)->missing('website.articles.0.body_json'));
        get('/articles?page=2')->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('website.articles', 2));
        get('/articles/future')->assertNotFound();
        get('/articles/secret-draft')->assertNotFound();
        DB::flushQueryLog();
        DB::enableQueryLog();
        resolve(GetPublicArticles::class)->handle(1)->getCollection()->map(resolve(GetPublicArticles::class)->card(...));
        expect(DB::getQueryLog())->toHaveCount(2);
        DB::disableQueryLog();
    });

    it('keeps old slugs reserved and resolves back changes without loops', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $article = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft());
        resolve(PublishWebsiteContent::class)->handle($actor, $article, 1);
        resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['slug' => 'second']), $article, 1);
        resolve(PublishWebsiteContent::class)->handle($actor, $article, 2);
        post('/website/articles', articleInput($actor))->assertSessionHasErrors('slug');
        resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(), $article, 2);
        resolve(PublishWebsiteContent::class)->handle($actor, $article, 3);
        get('/articles/second')->assertStatus(301)->assertRedirect(route('articles.show', ['slug' => 'company-news']));
        get('/articles/company-news')->assertOk();
    });

    it('gates every public read while keeping authorized preview available', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $article = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft());
        resolve(PublishWebsiteContent::class)->handle($actor, $article, 1);
        $settings = resolve(GeneralSettings::class);
        $settings->public_pages_enabled = false;
        $settings->save();
        get('/articles')->assertNotFound();
        get('/articles/company-news')->assertNotFound();
        get('/website/articles/'.$article->id.'/preview')->assertOk();
    });

    it('keeps draft cover images private and requires owned media with alt text', function (): void {
        Storage::fake('local');
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        post('/website/articles', articleInput($actor))->assertSessionHasNoErrors();
        $article = WebsiteContent::query()->whereNull('page_key')->sole();
        $base = '/website/articles/'.$article->id;
        post($base.'/image', ['image' => UploadedFile::fake()->image('cover.jpg')])->assertSessionHasNoErrors();
        $media = $article->getFirstMedia('website-images');
        get('/website/images/'.$media->id)->assertNotFound();
        post($base.'/draft', articleInput($actor, ['version' => 1, 'cover' => ['media_id' => $media->id, 'alt' => '']]))->assertSessionHasErrors('cover.alt');
        post($base.'/draft', articleInput($actor, ['version' => 1, 'cover' => ['media_id' => $media->id, 'alt' => 'Office']]))->assertSessionHasNoErrors();
        post($base.'/publish', ['version' => 2])->assertSessionHasNoErrors();
        get('/website/images/'.$media->id)->assertOk();
        post($base.'/unpublish')->assertSessionHasNoErrors();
        get('/website/images/'.$media->id)->assertNotFound();
    });

    it('lists bounded resource rows without exposing editor documents', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent);
        resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft());
        get('/website/articles?per_page=999')->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('articles.data', 1)->where('articles.meta.per_page', 10)->where('articles.data.0.title', 'Company news')->missing('articles.data.0.draft')->missing('articles.data.0.body_json'));
    });

    it('supplies recent and featured published home sections independently', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $featured = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['slug' => 'featured', 'featured' => true]));
        resolve(PublishWebsiteContent::class)->handle($actor, $featured, 1, now()->subDay()->toIso8601String());
        for ($n = 0; $n < 13; $n++) {
            $record = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['slug' => 'recent-'.$n]));
            resolve(PublishWebsiteContent::class)->handle($actor, $record, 1);
        }
        get('/')->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('website.articles', 12)->has('website.featuredArticles', 1)->where('website.featuredArticles.0.href', '/articles/featured'));
    });
});
