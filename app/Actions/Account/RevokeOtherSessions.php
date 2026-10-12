<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RevokeOtherSessions
{
    public function handle(User $user, string $currentSessionId): void
    {
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)->where('id', '!=', $currentSessionId)->delete();
        }
    }
}
