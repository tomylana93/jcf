<?php

declare(strict_types=1);

namespace App\Tables\Website;

use App\Models\WebsiteContent;
use App\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** @extends Table<WebsiteContent> */
final class WebsiteArticleTable extends Table
{
    /** @return Builder<WebsiteContent> */
    protected function query(): Builder
    {
        return WebsiteContent::query()->whereNull('page_key')->select(['id', 'draft', 'draft_version', 'published_at', 'published_slug', 'updated_at', 'publication_version']);
    }

    /** @return array<int, string> */
    protected function allowedFilters(): array
    {
        return [];
    }

    /** @return array<int, string> */
    protected function allowedSorts(): array
    {
        return ['updated_at', 'published_at'];
    }

    /** @return list<string> */
    protected function defaultSort(): array
    {
        return ['-updated_at', 'id'];
    }
}
