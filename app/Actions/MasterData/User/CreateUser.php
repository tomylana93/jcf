<?php

declare(strict_types=1);

namespace App\Actions\MasterData\User;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateUser
{
    /** @param array{name: string, email: string, password: string, role?: ?string} $data */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            $user->status = UserStatus::Active;
            $user->save();
            $user->syncRoles(isset($data['role']) ? [$data['role']] : []);

            return $user;
        });
    }
}
