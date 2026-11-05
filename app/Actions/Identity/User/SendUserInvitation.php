<?php

declare(strict_types=1);

namespace App\Actions\Identity\User;

use App\Actions\Audit\RecordAuditLog;
use App\Enums\AuditEvent;
use App\Enums\UserStatus;
use App\Jobs\MasterData\User\SendUserInvitation as SendUserInvitationJob;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final readonly class SendUserInvitation
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $user, ?User $actor = null): void
    {
        if ($user->invited_at === null || $user->invitation_accepted_at !== null || $user->status !== UserStatus::Active) {
            throw ValidationException::withMessages(['invitation' => __('user.invitation.unavailable')]);
        }

        $key = 'user-invitation:'.$user->id.':'.hash('sha256', $user->email);
        $sent = RateLimiter::attempt($key, 1, function () use ($user, $actor): void {
            DB::transaction(function () use ($user, $actor): void {
                $this->recordAuditLog->handle($actor, AuditEvent::UserInvitationQueued, 'user', $user->id, $user->name, [], ['queued' => true]);
                $actor?->notify(new ApplicationNotification('invitation_queued', $user->name));
                dispatch(new SendUserInvitationJob($user->id, $user->email))->afterCommit();
            });
        }, (int) config('auth.passwords.users.throttle'));

        if ($sent === false) {
            throw ValidationException::withMessages(['invitation' => __('passwords.throttled')]);
        }
    }
}
