<?php

declare(strict_types=1);

namespace App\Actions\Identity\User;

use App\Actions\Account\TerminateUserRealtimeConnections;
use App\Actions\Audit\RecordAuditLog;
use App\Enums\AuditEvent;
use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use Illuminate\Support\Facades\DB;

final class UpdateUserStatus
{
    public function __construct(
        public RecordAuditLog $recordAuditLog,
        private readonly TerminateUserRealtimeConnections $terminateRealtimeConnections,
    ) {}

    public function handle(User $user, UserStatus $status, ?User $actor = null): void
    {
        $wasActive = $user->status !== UserStatus::Disabled;

        DB::transaction(function () use ($user, $status, $actor): void {
            $before = ['status' => $user->status->value];
            $user->status = $status;
            $user->save();
            $log = $this->recordAuditLog->handle($actor, AuditEvent::UserStatusUpdated, 'user', $user->id, $user->name, $before, ['status' => $status->value]);
            if ($log !== null) {
                $user->notify(new ApplicationNotification('account_updated'));
            }
        });

        if ($wasActive && $status === UserStatus::Disabled) {
            $this->terminateRealtimeConnections->handle($user);
        }
    }
}
