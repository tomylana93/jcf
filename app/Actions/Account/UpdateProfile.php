<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

final class UpdateProfile
{
    /**
     * @param  array{name: string, email: string, timezone?: string|null}  $data
     */
    public function handle(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            $user->fill($data);

            if ($user->isDirty('email')) {
                $previousIdentity = clone $user;
                $previousIdentity->email = $user->getOriginal('email');
                Password::broker()->deleteToken($previousIdentity);
                $user->email_verified_at = null;
            }

            $user->save();
        });
    }
}
