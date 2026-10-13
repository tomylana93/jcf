<?php

declare(strict_types=1);

namespace App\Tables\Permission;

use App\Models\User;
use App\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;

/** @extends Table<User> */
final class UserPermissionTable extends Table
{
    /** @return Builder<User> */
    protected function query(): Builder
    {
        return User::query()->select(['id', 'name', 'email', 'status', 'created_at', 'invited_at', 'invitation_accepted_at', 'email_verified_at'])
            ->with(['roles.permissions', 'permissions', 'media']);
    }

    /** @return array<int, AllowedFilter|string> */
    protected function allowedFilters(): array
    {
        return [AllowedFilter::callback('search', function (Builder $query, mixed $value): void {
            $term = '%'.mb_strtolower(mb_trim((string) $value)).'%';
            $query->where(fn (Builder $query): Builder => $query->whereRaw('LOWER(name) LIKE ?', [$term])->orWhereRaw('LOWER(email) LIKE ?', [$term]));
        })];
    }

    /** @return array<int, string> */
    protected function allowedSorts(): array
    {
        return ['name', 'email'];
    }

    protected function defaultSort(): string
    {
        return 'name';
    }
}
