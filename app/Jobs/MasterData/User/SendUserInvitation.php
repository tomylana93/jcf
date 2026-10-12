<?php

declare(strict_types=1);

namespace App\Jobs\MasterData\User;

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\MasterData\User\UserInvitation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Password;

#[Backoff([60, 300])]
#[Tries(3)]
final class SendUserInvitation implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $userId, public string $email) {}

    public function handle(): void
    {
        $user = User::query()->find($this->userId);

        if ($user === null || $user->email !== $this->email || $user->invited_at === null || $user->invitation_accepted_at !== null || $user->status !== UserStatus::Active) {
            return;
        }

        $token = Password::broker()->createToken($user);
        $user->notify(new UserInvitation($token));
    }
}
