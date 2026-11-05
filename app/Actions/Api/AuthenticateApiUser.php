<?php

declare(strict_types=1);

namespace App\Actions\Api;

use App\Actions\Fortify\FindUserByCredentials;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final readonly class AuthenticateApiUser
{
    public function __construct(private FindUserByCredentials $findUserByCredentials) {}

    /**
     * Resolve the account for a mobile login with the same rules as the web login.
     *
     * @throws ValidationException
     */
    public function handle(string $email, string $password): User
    {
        return $this->findUserByCredentials->handle($email, $password)
            ?? throw ValidationException::withMessages(['email' => __('auth.failed')]);
    }
}
