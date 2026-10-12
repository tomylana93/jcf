<?php

declare(strict_types=1);

namespace App\Tables\MasterData;

use App\Enums\Role;
use App\Models\User;
use App\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;

/** @extends Table<User> */
final class UserTable extends Table
{
    /** @return Builder<User> */
    protected function query(): Builder
    {
        return User::query()->select(['id', 'name', 'email', 'status', 'created_at', 'email_verified_at'])
            ->with(['roles:id,name', 'media'])
            ->whereDoesntHave('roles', fn (Builder $query): Builder => $query->where('name', Role::SuperAdmin->value));
    }

    /** @return array<int, AllowedFilter|string> */
    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::callback('search', function (Builder $query, mixed $value): void {
                $search = mb_trim((string) $value);

                $term = '%'.Str::lower($search).'%';
                $query->where(fn (Builder $query): Builder => $query
                    ->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$term]));
            }),
            AllowedFilter::exact('status'),
            AllowedFilter::exact('role', 'roles.name'),
        ];
    }

    /** @return array<int, string> */
    protected function allowedSorts(): array
    {
        return ['name', 'email', 'status', 'created_at', 'id'];
    }

    /** @return array<int, string> */
    protected function defaultSort(): array
    {
        return ['-created_at', 'id'];
    }
}
