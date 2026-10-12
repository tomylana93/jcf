<?php

declare(strict_types=1);

namespace App\Actions\MasterData\User;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateUser
{
    /** @param array{name: string, email: string, password?: ?string, role?: ?string, send_invitation?: bool} $data */
    public function handle(array $data): User
    {
        $invited = $data['send_invitation'] ?? false;
        if (! $invited && blank($data['password'] ?? null)) {
            throw ValidationException::withMessages(['password' => __('validation.required', ['attribute' => 'password'])]);
        }

        return DB::transaction(function () use ($data, $invited): User {
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

            if ($data['send_invitation'] ?? false) {
                resolve(SendUserInvitation::class)->handle($user);
            }

            return $user;
        });
    }
}
