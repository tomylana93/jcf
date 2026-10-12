<?php

declare(strict_types=1);

namespace App\Tables\Audit;

use App\Models\AuditLog;
use App\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
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
            AllowedFilter::callback('search', function (Builder $query, mixed $value): void {
                $term = '%'.Str::lower(mb_trim((string) $value)).'%';
                $query->where(fn (Builder $query): Builder => $query
                    ->whereRaw('LOWER(actor_name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(subject_label) LIKE ?', [$term]));
            }),
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
