<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Gate;

final class GetChatRecipients
{
    /** @return CursorPaginator<int, User> */
    public function handle(User $actor, string $search): CursorPaginator
    {
        Gate::forUser($actor)->authorize('create', ChatConversation::class);
        $pattern = '%'.addcslashes($search, '%_\\').'%';

        return User::query()->select(['id', 'name'])->with('media')
            ->where('id', '!=', $actor->id)->where('status', UserStatus::Active)
            ->where(fn (Builder $query): Builder => $query
                ->whereHas('permissions', fn (Builder $query): Builder => $query->where('name', Permission::UseChat->value))
                ->orWhereHas('roles', fn (Builder $query): Builder => $query
                    ->where('name', Role::SuperAdmin->value)
                    ->orWhereHas('permissions', fn (Builder $query): Builder => $query->where('name', Permission::UseChat->value))))
            ->whereLike('name', $pattern)->orderBy('name')->orderBy('id')->cursorPaginate(20);
    }
}
