<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Actions\Chat\TerminateUserRealtimeConnections;
use App\Enums\UserStatus;
use App\Models\User;

final readonly class DisableAccount
{
    public function __construct(private TerminateUserRealtimeConnections $terminateRealtimeConnections) {}

    public function handle(User $user): void
    {
        $wasDisabled = $user->status === UserStatus::Disabled;

        $user->status = UserStatus::Disabled;
        $user->save();

        if (! $wasDisabled) {
            $this->terminateRealtimeConnections->handle($user);
        }
    }
}
