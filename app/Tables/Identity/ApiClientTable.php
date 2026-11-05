<?php

declare(strict_types=1);

namespace App\Tables\Identity;

use App\Models\ApiClient;
use App\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;

/** @extends Table<ApiClient> */
final class ApiClientTable extends Table
{
    /** @return Builder<ApiClient> */
    protected function query(): Builder
    {
        return ApiClient::query()->select(['id', 'name', 'description', 'status', 'created_at'])->withCount('tokens');
    }

    /** @return array<int, AllowedFilter|string> */
    protected function allowedFilters(): array
    {
        return [
            $this->searchFilter('name', 'description'),
            AllowedFilter::exact('status'),
        ];
    }

    /** @return array<int, string> */
    protected function allowedSorts(): array
    {
        return ['name', 'status', 'created_at', 'id'];
    }

    /** @return array<int, string> */
    protected function defaultSort(): array
    {
        return ['-created_at', 'id'];
    }
}
