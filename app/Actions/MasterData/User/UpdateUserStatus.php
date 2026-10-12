<?php

declare(strict_types=1);

namespace App\Actions\MasterData\User;

use App\Actions\Audit\RecordAuditLog;
use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use Illuminate\Support\Facades\DB;

final class UpdateUserStatus
{
    public function __construct(public RecordAuditLog $recordAuditLog) {}

    public function handle(User $user, UserStatus $status, ?User $actor = null): void
    {
        DB::transaction(function () use ($user, $status, $actor): void {
            $before = ['status' => $user->status->value];
            $user->status = $status;
            $user->save();
            $log = $this->recordAuditLog->handle($actor, 'user.status_updated', 'user', $user->id, $user->name, $before, ['status' => $status->value]);
            if ($log !== null) {
                $user->notify(new ApplicationNotification('account_updated'));
            }
        });
    }
}
