<?php

declare(strict_types=1);

namespace App\Actions\Api;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AuthenticateApiUser
{
    /**
     * Resolve the account for a mobile login with the same rules as the web login.
     *
     * @throws ValidationException
     */
    public function handle(string $email, string $password): User
    {
        $user = User::query()->where('email', config('fortify.lowercase_usernames') ? Str::lower($email) : $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        if ($user->status === UserStatus::Disabled) {
            throw ValidationException::withMessages(['email' => __('auth.disabled')]);
        }

        return $user;
    }
}
