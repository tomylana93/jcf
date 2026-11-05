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
        return [$this->searchFilter('name', 'email')];
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
