<?php

declare(strict_types=1);

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
use function Pest\Laravel\post;

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
