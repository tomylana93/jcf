<?php

declare(strict_types=1);

namespace App\Actions\MasterData\User;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

final class UpdateUser
{
    /** @param array{name: string, email: string, role?: ?string, status: string} $data */
    public function handle(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {
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
        });
    }
}
