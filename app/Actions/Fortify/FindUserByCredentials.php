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
final readonly class FindUserByCredentials
{
    /**
     * Return null for unknown credentials so the caller applies its own failure handling and throttling.
     * Unknown emails still pay the hashing cost, so response timing does not reveal which accounts exist.
     *
     * @throws ValidationException when the account is disabled
     */
    public function handle(string $email, string $password, string $errorField = 'email'): ?User
    {
        $user = User::query()->where('email', config('fortify.lowercase_usernames') ? Str::lower($email) : $email)->first();

        if ($user === null) {
            Hash::make($password);

            return null;
        }

        if (! Hash::check($password, $user->password)) {
            return null;
        }

        if ($user->status === UserStatus::Disabled) {
            throw ValidationException::withMessages([$errorField => __('auth.disabled')]);
        }

        return $user;
    }
}
