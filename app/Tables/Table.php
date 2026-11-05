<?php

declare(strict_types=1);

namespace App\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

/** @template TModel of Model */
abstract class Table
{
    /** @return Builder<TModel> */
    abstract protected function query(): Builder;

    /** @return array<int, AllowedFilter|string> */
    abstract protected function allowedFilters(): array;

    /** @return array<int, AllowedSort|string> */
    abstract protected function allowedSorts(): array;

    /** @return array<int, string>|string */
    abstract protected function defaultSort(): array|string;

    /** @return LengthAwarePaginator<int, mixed> */
    final public function paginate(Request $request): LengthAwarePaginator
    {
        $perPageOptions = $this->perPageOptions();
        $perPage = $request->integer('per_page', $perPageOptions[0]);

        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = $perPageOptions[0];
        }

        return $this->filteredQuery($request)->paginate($perPage)->withQueryString();
    }

    /**
     * Paginate and serialize each row through the table's JsonResource, as Inertia table pages expect.
     *
     * @param  class-string<JsonResource>  $resource
     * @return LengthAwarePaginator<int, array<mixed>>
     */
    final public function paginateResource(Request $request, string $resource): LengthAwarePaginator
    {
        return $this->paginate($request)->through(fn (Model $record): array => new $resource($record)->resolve($request));
    }

    /** @return Collection<int, TModel> */
    final public function exportRecords(Request $request): Collection
    {
        $records = $this->filteredQuery($request)->limit(1001)->get();
        if ($records->count() > 1000) {
            throw ValidationException::withMessages(['export' => __('table.export.error.limit')]);
        }

        return $records;
    }

    /**
     * Case-insensitive substring search across the given columns, treating `%` and `_` in the term literally.
     */
    protected function searchFilter(string ...$columns): AllowedFilter
    {
        return AllowedFilter::callback('search', function (Builder $query, mixed $value) use ($columns): void {
            $pattern = '%'.addcslashes(mb_trim((string) $value), '%_\\').'%';
            $query->where(function (Builder $query) use ($columns, $pattern): void {
                foreach ($columns as $column) {
                    $query->orWhereLike($column, $pattern);
                }
            });
        });
    }

    /** @return array<int, int> */
    protected function perPageOptions(): array
    {
        return [10, 25, 50, 100];
    }

    /** @return QueryBuilder<TModel> */
    private function filteredQuery(Request $request): QueryBuilder
    {
        return QueryBuilder::for($this->query(), $request)
            ->allowedFilters(...$this->allowedFilters())
            ->allowedSorts(...$this->allowedSorts())
            ->defaultSorts(...(array) $this->defaultSort());
    }
}
