<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\Actions\Audit\RecordAuditLog;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class UpdateUserPermissions
{
    public function __construct(public RecordAuditLog $recordAuditLog) {}

    /** @param list<string> $permissions */
    public function handle(User $user, User $actor, array $permissions): void
    {
        DB::transaction(function () use ($user, $actor, $permissions): void {
            $target = User::query()->lockForUpdate()->findOrFail($user->id);
            $before = $target->getDirectPermissions()->pluck('name')->sort()->values()->all();
            $permissions = array_values(array_diff($permissions, $target->getPermissionsViaRoles()->pluck('name')->all()));
            sort($permissions);
            $target->syncPermissions($permissions);

            if ($before !== $permissions) {
                $this->recordAuditLog->handle($actor, 'user.permissions_updated', 'user', $target->id, $target->name, ['permissions' => $before], ['permissions' => $permissions]);
                $target->notify(new ApplicationNotification('permissions_updated'));
                Log::info('User direct permissions updated', [
                    'actor_id' => $actor->id,
                    'user_id' => $target->id,
                    'before' => $before,
                    'after' => $permissions,
                    'changed_at' => now()->toIso8601String(),
                ]);
            }
        });
    }
}
