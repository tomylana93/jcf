<?php

declare(strict_types=1);

namespace App\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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

    /** @return Collection<int, TModel> */
    final public function exportRecords(Request $request): Collection
    {
        $records = $this->filteredQuery($request)->limit(1001)->get();
        if ($records->count() > 1000) {
            throw ValidationException::withMessages(['export' => __('table.export.error.limit')]);
        }

        return $records;
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
