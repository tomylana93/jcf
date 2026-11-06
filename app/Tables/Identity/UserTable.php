<?php

declare(strict_types=1);

namespace App\Tables\Identity;

use App\Enums\Role;
use App\Models\User;
use App\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;

/** @extends Table<User> */
final class UserTable extends Table
{
    /** @return Builder<User> */
    protected function query(): Builder
    {
        return User::query()->select(['id', 'name', 'email', 'status', 'created_at', 'email_verified_at', 'invited_at', 'invitation_accepted_at'])
            ->with(['roles:id,name', 'roles.permissions:id,name', 'permissions:id,name', 'media'])
            ->whereDoesntHave('roles', fn (Builder $query): Builder => $query->where('name', Role::SuperAdmin->value));
    }

    /** @return array<int, AllowedFilter|string> */
    protected function allowedFilters(): array
    {
        return [
            $this->searchFilter('name', 'email'),
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
