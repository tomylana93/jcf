<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Enums\WebsitePage;
use App\Models\WebsiteContent;
use Illuminate\Support\Arr;

final readonly class GetWebsitePageEditor
{
    public function __construct(private ListWebsiteImages $images) {}

    /** @return array<string, mixed> */
    public function handle(WebsitePage $page): array
    {
        $record = WebsiteContent::query()->where('page_key', $page->value)->first();

        return [
            'page' => ['page' => $page->value, 'label' => $page->label()],
            'content' => $record === null ? null : $this->content($record),
            'status' => $record === null ? null : [
                'draft_version' => $record->draft_version,
                'published' => $record->published !== null,
                'published_at' => $record->published_at?->toIso8601String(),
            ],
            'images' => $record === null ? [] : $this->images->handle($record),
        ];
    }

    /**
     * Working draft fields only; derived render output is rebuilt on save and preview.
     *
     * @return array<string, mixed>
     */
    private function content(WebsiteContent $record): array
    {
        $draft = Arr::except($record->draft, ['slug', 'body_html', 'body_text']);

        return [...$draft, 'version' => $record->draft_version];
    }
}
