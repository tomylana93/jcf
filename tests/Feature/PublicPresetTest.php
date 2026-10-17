<?php

declare(strict_types=1);

use App\Actions\Website\BuildPublicHome;
use App\Actions\Website\PublishWebsiteContent;
use App\Actions\Website\SaveWebsiteDraft;
use App\Actions\Website\UnpublishWebsiteContent;
use App\Actions\Website\UploadWebsiteImage;
use App\Enums\Permission;
use App\Settings\GeneralSettings;
use App\Settings\WebsiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

describe('Public preset presentation', function (): void {
    it('uses the selected preset and preserves isolated presentation preferences', function (string $preset): void {
        $general = resolve(GeneralSettings::class);
        $general->public_pages_enabled = true;
        $general->save();
        $settings = resolve(WebsiteSettings::class);
        $settings->preset = $preset;
        $settings->preferences[$preset] = ['sectionOrder' => ['contact', 'hero'], 'hiddenSections' => ['services']];
        $settings->save();

        get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('website.preset', $preset)
            ->where('website.preferences.'.$preset.'.sectionOrder', ['contact', 'hero'])
            ->where('website.preferences.'.$preset.'.hiddenSections', ['services'])
            ->where('website.page.kind', 'home')
            ->where('website.page.sections.0.title', $general->site_name));
    })->with(['corporate', 'editorial', 'minimal']);

    it('renders only the published home snapshot while edits remain private', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $content = resolve(SaveWebsiteDraft::class)->handle($actor, Arr::except(websiteDraft(['title' => 'Published organization']), ['slug']), pageKey: 'home');
        resolve(PublishWebsiteContent::class)->handle($actor, $content, 1);
        resolve(SaveWebsiteDraft::class)->handle($actor, Arr::except(websiteDraft(['title' => 'Private revision']), ['slug']), $content, 1);

        get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('website.page.title', 'Published organization')
            ->missing('website.page.body_json')
            ->missing('website.page.author_id')
            ->missing('website.page.draft'));
        resolve(UnpublishWebsiteContent::class)->handle($actor, $content);
        expect(resolve(BuildPublicHome::class)->handle()['page']['title'])->not->toBe('Published organization');
    });

    it('serves only currently published referenced images and revokes access after unpublish', function (): void {
        Storage::fake('local');
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $content = resolve(SaveWebsiteDraft::class)->handle($actor, Arr::except(websiteDraft(), ['slug']), pageKey: 'home');
        $image = resolve(UploadWebsiteImage::class)->handle($actor, $content, UploadedFile::fake()->image('cover.png'));
        $unused = resolve(UploadWebsiteImage::class)->handle($actor, $content, UploadedFile::fake()->image('unused.png'));
        get(route('website.image', $image->id))->assertNotFound();
        resolve(SaveWebsiteDraft::class)->handle($actor, Arr::except(websiteDraft(['cover' => ['media_id' => $image->id, 'alt' => 'Published image']]), ['slug']), $content, 1);
        resolve(PublishWebsiteContent::class)->handle($actor, $content, 2);

        get('/')->assertInertia(fn (Assert $page) => $page->where('website.page.cover', ['url' => route('website.image', $image->id, false), 'alt' => 'Published image']));
        get(route('website.image', $image->id))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        get(route('website.image', $unused->id))->assertNotFound();
        resolve(UnpublishWebsiteContent::class)->handle($actor, $content);
        get(route('website.image', $image->id))->assertNotFound();
    });

    it('returns no presentation and no image when public access is disabled', function (): void {
        $settings = resolve(GeneralSettings::class);
        $settings->public_pages_enabled = false;
        $settings->save();

        expect(resolve(BuildPublicHome::class)->handle())->toBeNull();
        get('/website/images/1')->assertNotFound();
    });
});
