<?php

declare(strict_types=1);

namespace App\Actions\Search;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Documentation;
use App\Models\User;
use App\Support\FeatureManager;
use Illuminate\Database\Eloquent\Builder;

final readonly class SearchGlobal
{
    public function __construct(private FeatureManager $features) {}

    /** @return array{documents: list<array{id: string, title: string, summary: string|null, slug: string}>, users: list<array{id: string, name: string, email: string}>} */
    public function handle(User $user, string $query): array
    {
        $documents = [];
        if ($this->features->enabled('documentation') && $user->can('viewAny', Documentation::class)) {
            $documents = Documentation::query()
                ->select(['id', 'title', 'summary', 'slug'])
                ->unless($user->can(Permission::ManageDocumentation), fn (Builder $builder): Builder => $builder->where('status', 'published'))
                ->whereRaw("search_vector @@ websearch_to_tsquery('simple', ?)", [$query])
                ->orderByRaw("ts_rank(search_vector, websearch_to_tsquery('simple', ?)) DESC", [$query])
                ->orderBy('id')->limit(5)->get()
                ->map(fn (Documentation $document): array => ['id' => $document->id, 'title' => $document->title, 'summary' => $document->summary, 'slug' => $document->slug])->values()->all();
        }

        $users = [];
        if ($user->can('viewAny', User::class)) {
            $pattern = '%'.addcslashes($query, '%_\\').'%';
            $users = User::query()->select(['id', 'name', 'email'])
                ->whereDoesntHave('roles', fn (Builder $builder): Builder => $builder->where('name', Role::SuperAdmin->value))
                ->where(fn (Builder $builder): Builder => $builder->whereLike('name', $pattern)->orWhereLike('email', $pattern))
                ->orderBy('name')->orderBy('id')->limit(5)->get()
                ->map(fn (User $target): array => ['id' => $target->id, 'name' => $target->name, 'email' => $target->email])->values()->all();
        }

        return ['documents' => array_values($documents), 'users' => array_values($users)];
    }
}
