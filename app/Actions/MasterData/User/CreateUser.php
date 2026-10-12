<?php

declare(strict_types=1);

namespace App\Actions\MasterData\User;

use App\Actions\Audit\GetUserAuditValues;
use App\Actions\Audit\RecordAuditLog;
use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateUser
{
    public function __construct(public RecordAuditLog $recordAuditLog, public GetUserAuditValues $getAuditValues) {}

    /** @param array{name: string, email: string, password?: ?string, role?: ?string, send_invitation?: bool} $data */
    public function handle(array $data, ?User $actor = null): User
    {
        $invited = $data['send_invitation'] ?? false;
        if (! $invited && blank($data['password'] ?? null)) {
            throw ValidationException::withMessages(['password' => __('validation.required', ['attribute' => 'password'])]);
        }

        return DB::transaction(function () use ($data, $invited, $actor): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $invited ? Str::random(64) : ($data['password'] ?? ''),
            ]);
            $user->status = UserStatus::Active;
            if ($data['send_invitation'] ?? false) {
                $user->invited_at = now();
            }
            $user->save();
            $user->syncRoles(isset($data['role']) ? [$data['role']] : []);

            $this->recordAuditLog->handle($actor, 'user.created', 'user', $user->id, $user->name, [], $this->getAuditValues->handle($user));
            $user->notify(new ApplicationNotification('account_created'));

            if ($data['send_invitation'] ?? false) {
                resolve(SendUserInvitation::class)->handle($user, $actor);
            }

            return $user;
        });
    }
}
