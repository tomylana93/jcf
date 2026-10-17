<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Enums\WebsitePage;
use App\Enums\WebsitePreset;
use App\Models\WebsiteContent;
use App\Settings\GeneralSettings;
use App\Settings\WebsiteSettings;
use Illuminate\Support\Arr;

final readonly class BuildWebsitePreview
{
    public function __construct(
        private WebsiteSettings $settings,
        private GeneralSettings $general,
        private ValidateWebsiteContent $validateContent,
    ) {}

    /**
     * Build the preview website payload the same way the public home payload is built.
     *
     * @return array<string, mixed>
     */
    public function handle(WebsitePage $page, ?WebsitePreset $override, bool $withDraft): array
    {
        $this->settings->refresh();
        $preset = $override ?? WebsitePreset::from($this->settings->preset);
        $record = WebsiteContent::query()->where('page_key', $page->value)->first();
        $content = null;
        $mode = 'empty';
        if ($record !== null) {
            $content = $withDraft ? $this->draftContent($record) : $this->publishedContent($record);
            $mode = $content === null ? 'empty' : ($withDraft ? 'draft' : 'published');
        }

        return [
            'preset' => $preset->value,
            'preferences' => $this->settings->preferences,
            'navigation' => [['label' => __('navigation.link.home'), 'href' => route('home', absolute: false), 'active' => true]],
            'footer' => ['text' => $this->settings->footer['text'], 'links' => []],
            'preview' => ['mode' => $mode],
            'page' => [
                'kind' => $page->value,
                'title' => $this->title($page, $content),
                'sections' => $this->sections($page, $content),
                'body' => $content['body_html'] ?? '',
                'cover' => $this->image($content['cover'] ?? null),
                'seo' => [
                    'title' => $content['seo']['title'] ?? $this->title($page, $content),
                    'description' => $content['seo']['description'] ?? ($page->value === 'home' ? $this->general->site_description : $this->settings->seo['description']),
                    'canonicalUrl' => mb_rtrim((string) config('app.url'), '/').'/',
                    'robots' => $content['seo']['robots'] ?? $this->settings->seo['robots'],
                    'locale' => app()->getLocale(),
                    'image' => $this->image($content['seo']['image'] ?? null),
                ],
            ],
        ];
    }

    /**
     * The working draft, sanitized the same way as a published snapshot.
     *
     * @return array<string, mixed>
     */
    private function draftContent(WebsiteContent $record): array
    {
        // Stored drafts keep derived fields that validation refreshes; editor-only fields stay out.
        $validated = $this->validateContent->handle(
            Arr::except($record->draft, ['slug', 'body_html', 'body_text']),
            $record,
            false,
        );
        $sections = $validated['sections'];
        foreach ($sections as $index => $section) {
            // Only rendered representations are exposed; editor JSON stays out of every payload.
            unset($sections[$index]['body_json']);
        }

        return Arr::only($validated, ['title', 'sections', 'body_html', 'cover', 'seo']);
    }

    /**
     * The published snapshot, resolved only after the publication date is visible.
     *
     * @return array<string, mixed>|null
     */
    private function publishedContent(WebsiteContent $record): ?array
    {
        if ($record->published === null || $record->published_at === null || $record->published_at->isFuture()) {
            return null;
        }

        return $record->published;
    }

    /**
     * @param  array<string, mixed>|null  $content
     */
    private function title(WebsitePage $page, ?array $content): string
    {
        return $content['title'] ?? ($page->value === 'home'
            ? ($this->settings->identity ?: $this->general->site_name)
            : $page->label());
    }

    /**
     * Published snapshots keep section structure; unstarted home pages keep the public fallback hero.
     *
     * @param  array<string, mixed>|null  $content
     * @return list<array<string, mixed>>
     */
    private function sections(WebsitePage $page, ?array $content): array
    {
        if ($content !== null) {
            $sections = $content['sections'];
            foreach ($sections as $index => $section) {
                foreach (['image'] as $mediaKey) {
                    if (isset($section[$mediaKey])) {
                        $section[$mediaKey] = $this->image($section[$mediaKey]);
                    }
                }
                $sections[$index] = $section;
            }

            return $sections;
        }
        if ($page->value !== 'home') {
            return [];
        }

        return [['id' => 'public-introduction', 'type' => 'hero', 'visible' => true, 'title' => $this->title($page, null), 'description' => $this->general->site_description, 'image' => null, 'cta' => ['label' => __('navigation.link.explore'), 'href' => '#public-introduction', 'active' => false]]];
    }

    /** @param array{media_id: int, alt: string}|null $image
     * @return array{url: string, alt: string}|null
     */
    private function image(?array $image): ?array
    {
        return $image === null ? null : ['url' => route('website.preview.image', ['image' => $image['media_id']], absolute: false), 'alt' => $image['alt']];
    }
}
