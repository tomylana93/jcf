<?php

declare(strict_types=1);

namespace App\Tables\Audit;

use App\Models\AuditLog;
use App\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;

/** @extends Table<AuditLog> */
final class AuditLogTable extends Table
{
    /** @return Builder<AuditLog> */
    protected function query(): Builder
    {
        return AuditLog::query();
    }

    /** @return array<int, AllowedFilter|string> */
    protected function allowedFilters(): array
    {
        return [
            $this->searchFilter('actor_name', 'subject_label'),
            AllowedFilter::exact('event'),
        ];
    }

    /** @return array<int, string> */
    protected function allowedSorts(): array
    {
        return ['created_at'];
    }

    /** @return list<string> */
    protected function defaultSort(): array
    {
        return ['-created_at', '-id'];
    }
}
