<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Login rules shared by the web and mobile logins: matching credentials of an account that is not disabled.
 */
final class FindUserByCredentials
{
    /**
     * Return null for unknown credentials so the caller applies its own failure handling and throttling.
     *
     * @throws ValidationException when the account is disabled
     */
    public function handle(string $email, string $password, string $errorField = 'email'): ?User
    {
        $user = User::query()->where('email', config('fortify.lowercase_usernames') ? Str::lower($email) : $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            return null;
        }

        if ($user->status === UserStatus::Disabled) {
            throw ValidationException::withMessages([$errorField => __('auth.disabled')]);
        }

        return $user;
    }
}
