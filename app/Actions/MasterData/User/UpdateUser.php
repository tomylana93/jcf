<?php

declare(strict_types=1);

namespace App\Actions\MasterData\User;

use App\Actions\Audit\GetUserAuditValues;
use App\Actions\Audit\RecordAuditLog;
use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

final class UpdateUser
{
    public function __construct(public RecordAuditLog $recordAuditLog, public GetUserAuditValues $getAuditValues) {}

    /** @param array{name: string, email: string, role?: ?string, status: string} $data */
    public function handle(User $user, array $data, ?User $actor = null): void
    {
        DB::transaction(function () use ($user, $data, $actor): void {
            $before = $this->getAuditValues->handle($user);
            $emailChanged = $user->email !== $data['email'];
            $user->fill(['name' => $data['name'], 'email' => $data['email']]);
            $user->status = UserStatus::from($data['status']);

            if ($user->isDirty('email')) {
                $previousIdentity = clone $user;
                $previousIdentity->email = $user->getOriginal('email');
                Password::broker()->deleteToken($previousIdentity);
                $user->email_verified_at = null;
            }

            $user->save();
            $user->syncRoles(isset($data['role']) ? [$data['role']] : []);

            $log = $this->recordAuditLog->handle($actor, 'user.updated', 'user', $user->id, $user->name, $before, $this->getAuditValues->handle($user));
            if ($log !== null) {
                $user->notify(new ApplicationNotification('account_updated'));
            }

            if ($emailChanged && $user->invited_at !== null && $user->invitation_accepted_at === null && $user->status === UserStatus::Active) {
                resolve(SendUserInvitation::class)->handle($user, $actor);
            }
        });
    }
}
