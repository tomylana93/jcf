<?php

declare(strict_types=1);

use App\Actions\Website\PublishWebsiteContent;
use App\Actions\Website\SaveWebsiteDraft;
use App\Actions\Website\UploadWebsiteImage;
use App\Enums\Permission;
use App\Settings\GeneralSettings;
use App\Settings\WebsiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\get;

describe('Article browser flows', function (): void {
    it('saves a working article draft using the existing rich text editor', function (): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $article = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft());
        $page = visit('/website/articles/'.$article->id.'/edit')->assertSee(__('article.title.edit'))
            ->type('#article-title', 'Edited in browser')
            ->type('#article-slug', 'browser-news')
            ->press(__('website.button.save_draft'))
            ->assertSee(__('website.status.draft_saved'))
            ->assertNoJavaScriptErrors()
            ->screenshot(filename: 'scope-five-article-editor-desktop');
        visit('/website/articles/'.$article->id.'/edit')->on()->mobile()->assertSee(__('article.title.edit'))->assertNoJavaScriptErrors()->screenshot(filename: 'scope-five-article-editor-mobile');
        expect($article->fresh()->draft['title'])->toBe('Edited in browser')
            ->and($article->fresh()->draft['slug'])->toBe('browser-news')
            ->and($article->fresh()->published)->toBeNull();
    });

    it('uploads and attaches an article cover without losing draft edits', function (): void {
        Storage::fake('local');
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $article = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft());
        $file = UploadedFile::fake()->image('cover.png', 120, 80);
        $path = storage_path('framework/testing/website-browser-cover.png');
        copy($file->getPathname(), $path);
        // Pest's multipart parser uses mb_ltrim on binary image data.
        $encoding = mb_internal_encoding();
        mb_internal_encoding('8bit');
        try {
            $page = visit('/website/articles/'.$article->id.'/edit')
                ->type('#article-title', 'Draft with uploaded cover')
                ->click('[data-image-field="Cover image"] [data-test="choose_website_image"]')
                ->click('[data-test="choose-background"]')
                ->attach('.uppy-Dashboard-input:not([webkitdirectory])', $path)
                ->press(__('common.button.done'))
                ->click('[data-test="upload_website_image"]')
                ->assertSee('cover')
                ->press(__('common.button.cancel'))
                ->assertValue('#article-title', 'Draft with uploaded cover')
                ->type('[data-image-field="Cover image"] input', 'Uploaded cover description')
                ->press(__('website.button.save_draft'))
                ->assertSee(__('website.status.draft_saved'))
                ->click('[data-image-field="Social sharing image"] [data-test="choose_website_image"]')
                ->click('[data-media-id]')
                ->type('[data-image-field="Social sharing image"] input', 'Sharing image description')
                ->press(__('website.button.save_draft'))
                ->assertSee(__('website.status.draft_saved'))
                ->click('#article-date')
                ->assertVisible('[data-slot="calendar"]')
                ->click('[data-slot="calendar-cell-trigger"]:not([data-outside-view]):not([data-disabled]) >> nth=0')
                ->assertValue('#article-time', '00:00')
                ->press(__('article.button.clear_date'))
                ->assertSee(__('article.placeholder.date'))
                ->screenshot(filename: 'website-editor-refactor-desktop')
                ->press(__('website.button.publish'))
                ->click('[role="alertdialog"] button:has-text("'.__('website.button.publish').'")')
                ->assertSee(__('website.status.published_toast'))
                ->press(__('website.button.unpublish'))
                ->click('[role="alertdialog"] button:has-text("'.__('website.button.unpublish').'")')
                ->assertSee(__('website.status.unpublished_toast'))
                ->assertNoJavaScriptErrors();
            expect($article->fresh()->draft['cover']['alt'])->toBe('Uploaded cover description')
                ->and($article->fresh()->draft['seo']['image']['alt'])->toBe('Sharing image description')
                ->and($article->fresh()->draft['cover']['media_id'])->toBe($article->getFirstMedia('website-images')->id);
        } finally {
            mb_internal_encoding($encoding);
            File::delete($path);
        }
    });

    it('uploads page SEO images and retains unsaved page fields', function (): void {
        Storage::fake('local');
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent);
        $draft = Arr::except(websiteDraft(), ['slug']);
        $content = resolve(SaveWebsiteDraft::class)->handle($actor, $draft, pageKey: 'home');
        $file = UploadedFile::fake()->image('sharing.png', 120, 80);
        $path = storage_path('framework/testing/website-page-sharing.png');
        copy($file->getPathname(), $path);
        // Preserve image bytes in Pest's multipart parser.
        $encoding = mb_internal_encoding();
        mb_internal_encoding('8bit');
        try {
            $page = visit('/website/content/home')->on()->mobile()
                ->type('#website_page_title', 'Page with uploaded SEO image')
                ->click('[data-image-field="Social sharing image"] [data-test="choose_website_image"]')
                ->click('[data-test="choose-background"]')
                ->attach('.uppy-Dashboard-input:not([webkitdirectory])', $path)
                ->press(__('common.button.done'))
                ->click('[data-test="upload_website_image"]')
                ->assertVisible('[data-media-id]')
                ->press(__('common.button.cancel'))
                ->assertValue('#website_page_title', 'Page with uploaded SEO image')
                ->type('[data-image-field="Social sharing image"] input', 'Page sharing description')
                ->click('[data-test="save_website_draft"]')
                ->assertSee(__('website.status.draft_saved'))
                ->assertNoJavaScriptErrors()
                ->screenshot(filename: 'website-page-refactor-mobile');
            expect($content->fresh()->draft['title'])->toBe('Page with uploaded SEO image')
                ->and($content->fresh()->draft['seo']['image']['alt'])->toBe('Page sharing description')
                ->and($content->fresh()->draft['seo']['image']['media_id'])->toBe($content->getFirstMedia('website-images')->id);
        } finally {
            mb_internal_encoding($encoding);
            File::delete($path);
        }
    });

    it('renders readable article pages and navigation in every preset', function (string $preset): void {
        $actor = actingAsUserWithPermissions(Permission::EditWebsiteContent, Permission::PublishWebsiteContent);
        $general = resolve(GeneralSettings::class);
        resolve(WebsiteSettings::class)->fill(['public_pages_enabled' => true])->save();
        $general->save();
        $settings = resolve(WebsiteSettings::class);
        $settings->preset = $preset;
        $settings->save();
        Storage::fake('local');
        $body = ['type' => 'doc', 'content' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'This demonstration article explains how published updates stay readable across presentation presets. The paragraphs, links and cover image are test fixtures for the website reading experience.']]],
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'A clear reading structure']]],
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Headings separate topics, while short paragraphs help readers follow the details on a phone or a larger screen. The layout keeps a comfortable line length and consistent spacing.']]],
            ['type' => 'bulletList', 'content' => [
                ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Read the approved publication and its author.']]]]],
                ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Follow breadcrumbs to return to the article list.']]]]],
            ]],
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Browse more articles', 'marks' => [['type' => 'link', 'attrs' => ['href' => '/articles']]]]]],
        ]];
        $article = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['title' => 'Public reading example: a clear publication with a longer title for desktop and mobile', 'body_json' => $body]));
        $cover = resolve(UploadWebsiteImage::class)->handle($actor, $article, UploadedFile::fake()->image('reading-fixture.jpg', 1200, 800));
        $article = resolve(SaveWebsiteDraft::class)->handle($actor, websiteDraft(['title' => 'Public reading example: a clear publication with a longer title for desktop and mobile', 'body_json' => $body, 'cover' => ['media_id' => $cover->id, 'alt' => 'Demonstration cover fixture']]), $article, 1);
        resolve(PublishWebsiteContent::class)->handle($actor, $article, $article->draft_version);
        $payload = get('/articles/company-news')->assertOk()->viewData('page');
        $script = <<<'JS_WRAP'
        const chunks = [];
        for await (const chunk of process.stdin) chunks.push(chunk);
        const { default: render } = await import('./bootstrap/ssr/app.js');
        const result = await render(JSON.parse(Buffer.concat(chunks).toString()));
        await new Promise(resolve => process.stdout.write('ARTICLE_SSR_RESULT:' + JSON.stringify(result) + 'ARTICLE_SSR_END', resolve));
        process.exit(0);
        JS_WRAP;
        $result = Process::path(base_path())->input(json_encode($payload, JSON_THROW_ON_ERROR))->run(['node', '--input-type=module', '-e', $script]);
        expect($result->exitCode())->toBe(0, $result->errorOutput());
        $output = explode('ARTICLE_SSR_RESULT:', $result->output(), 2);
        expect($output)->toHaveCount(2);
        $rendered = json_decode(explode('ARTICLE_SSR_END', $output[1], 2)[0], true, flags: JSON_THROW_ON_ERROR);
        expect($rendered['body'])->toContain('<h1', 'Public reading example', 'A clear reading structure', 'Demonstration cover fixture', '<ul')
            ->and(implode('', $rendered['head']))->toContain('rel="canonical"', 'name="description"');
        $page = visit('/articles')->assertSee('Public reading example')
            ->click('a[href="/articles/company-news"]')->assertPathIs('/articles/company-news')
            ->assertSee('Public reading example')->assertSee($actor->name)
            ->assertNoJavaScriptErrors()->screenshot(filename: 'scope-five-'.$preset.'-article-desktop');
        visit('/articles/company-news')->on()->mobile()->assertSee('Public reading example')->assertNoJavaScriptErrors()->screenshot(filename: 'scope-five-'.$preset.'-article-mobile');
    })->with(['corporate', 'editorial', 'minimal']);
});
