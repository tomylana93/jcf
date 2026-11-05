<?php

declare(strict_types=1);

namespace App\Actions\Documentation;

use App\Enums\Module;
use App\Enums\Permission;
use App\Models\Documentation;
use App\Models\User;
use App\Support\FeatureManager;
use App\Support\SearchProvider;
use Illuminate\Database\Eloquent\Builder;

final readonly class SearchDocuments implements SearchProvider
{
    public function __construct(private FeatureManager $features) {}

    public function key(): string
    {
        return 'documents';
    }

    /** @return list<array{id: string, title: string, summary: string|null, slug: string}> */
    public function handle(User $user, string $query): array
    {
        $documents = [];
        if ($this->features->enabled(Module::Documentation) && $user->can('viewAny', Documentation::class)) {
            $documents = Documentation::query()
                ->select(['id', 'title', 'summary', 'slug'])
                ->unless($user->can(Permission::ManageDocumentation), fn (Builder $builder): Builder => $builder->where('status', 'published'))
                ->whereRaw("search_vector @@ websearch_to_tsquery('simple', ?)", [$query])
                ->orderByRaw("ts_rank(search_vector, websearch_to_tsquery('simple', ?)) DESC", [$query])
                ->orderBy('id')->limit(5)->get()
                ->map(fn (Documentation $document): array => ['id' => $document->id, 'title' => $document->title, 'summary' => $document->summary, 'slug' => $document->slug])->values()->all();
        }

        return array_values($documents);
    }
}
