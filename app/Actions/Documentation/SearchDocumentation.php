<?php

declare(strict_types=1);

namespace App\Actions\Documentation;

use App\Enums\DocumentationStatus;
use App\Models\Documentation;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class SearchDocumentation
{
    /** @return LengthAwarePaginator<int, Documentation> */
    public function handle(string $search, bool $manage = false): LengthAwarePaginator
    {
        $query = Documentation::query()->select(['id', 'title', 'slug', 'summary', 'status', 'published_at', 'updated_at']);
        if (! $manage) {
            $query->where('status', DocumentationStatus::Published);
        }
        $search = mb_trim($search);
        if ($search !== '') {
            $query->whereRaw("search_vector @@ websearch_to_tsquery('simple', ?)", [$search])
                ->orderByRaw("ts_rank(search_vector, websearch_to_tsquery('simple', ?)) DESC", [$search]);
        }

        return $query->latest('published_at')->orderByDesc('id')->paginate(12)->withQueryString();
    }
}
