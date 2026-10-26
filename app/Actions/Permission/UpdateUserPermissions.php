<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\Actions\Audit\RecordAuditLog;
use App\Enums\Permission;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use App\Support\FeatureManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final readonly class UpdateUserPermissions
{
    public function __construct(public RecordAuditLog $recordAuditLog, private FeatureManager $features) {}

    /** @param list<string> $permissions */
    public function handle(User $user, User $actor, array $permissions): void
    {
        $this->ensureAvailable($permissions);

        DB::transaction(function () use ($user, $actor, $permissions): void {
            $target = User::query()->lockForUpdate()->findOrFail($user->id);
            $before = $target->getDirectPermissions()->pluck('name')->sort()->values()->all();
            $hidden = $target->getDirectPermissions()->pluck('name')
                ->map(fn (mixed $name): string => (string) $name)
                ->filter(fn (string $name): bool => ! $this->available($name))
                ->values()
                ->all();
            $permissions = array_values(array_diff([...$permissions, ...$hidden], $target->getPermissionsViaRoles()->pluck('name')->all()));
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

    /** @param list<string> $permissions */
    private function ensureAvailable(array $permissions): void
    {
        foreach ($permissions as $permission) {
            if (! $this->available($permission)) {
                throw ValidationException::withMessages(['permissions' => __('permission.error.module_disabled')]);
            }
        }
    }

    private function available(string $name): bool
    {
        return $this->features->moduleEnabled(Permission::tryFrom($name)?->module());
    }
}
