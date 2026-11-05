<?php

declare(strict_types=1);

namespace App\Actions\Identity\User;

use App\Actions\Account\PrepareEmailChange;
use App\Actions\Account\TerminateUserRealtimeConnections;
use App\Actions\Audit\GetUserAuditValues;
use App\Actions\Audit\RecordAuditLog;
use App\Enums\AuditEvent;
use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use Illuminate\Support\Facades\DB;

final readonly class UpdateUser
{
    public function __construct(
        private RecordAuditLog $recordAuditLog,
        private GetUserAuditValues $getAuditValues,
        private TerminateUserRealtimeConnections $terminateRealtimeConnections,
        private PrepareEmailChange $prepareEmailChange,
        private SendUserInvitation $sendInvitation,
    ) {}

    /** @param array{name: string, email: string, role?: ?string, status: string} $data */
    public function handle(User $user, array $data, ?User $actor = null): void
    {
        $wasDisabled = $user->status === UserStatus::Disabled;

        DB::transaction(function () use ($user, $data, $actor): void {
            $before = $this->getAuditValues->handle($user);
            $emailChanged = $user->email !== $data['email'];
            $user->fill(['name' => $data['name'], 'email' => $data['email']]);
            $user->status = UserStatus::from($data['status']);

            $this->prepareEmailChange->handle($user);

            $user->save();
            $user->syncRoles(isset($data['role']) ? [$data['role']] : []);

            $log = $this->recordAuditLog->handle($actor, AuditEvent::UserUpdated, 'user', $user->id, $user->name, $before, $this->getAuditValues->handle($user));
            if ($log !== null) {
                $user->notify(new ApplicationNotification('account_updated'));
            }

            if ($emailChanged && $user->invited_at !== null && $user->invitation_accepted_at === null && $user->status === UserStatus::Active) {
                $this->sendInvitation->handle($user, $actor);
            }
        });

        if (! $wasDisabled && $user->status === UserStatus::Disabled) {
            $this->terminateRealtimeConnections->handle($user);
        }
    }
}
