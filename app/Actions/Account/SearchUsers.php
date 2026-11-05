<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Enums\Role;
use App\Models\User;
use App\Support\SearchProvider;
use Illuminate\Database\Eloquent\Builder;

final class SearchUsers implements SearchProvider
{
    public function key(): string
    {
        return 'users';
    }

    /** @return list<array{id: string, name: string, email: string}> */
    public function handle(User $user, string $query): array
    {
        $users = [];
        if ($user->can('viewAny', User::class)) {
            $pattern = '%'.addcslashes($query, '%_\\').'%';
            $users = User::query()->select(['id', 'name', 'email'])
                ->whereDoesntHave('roles', fn (Builder $builder): Builder => $builder->where('name', Role::SuperAdmin->value))
                ->where(fn (Builder $builder): Builder => $builder->whereLike('name', $pattern)->orWhereLike('email', $pattern))
                ->orderBy('name')->orderBy('id')->limit(5)->get()
                ->map(fn (User $target): array => ['id' => $target->id, 'name' => $target->name, 'email' => $target->email])->values()->all();
        }

        return $users;
    }
}
