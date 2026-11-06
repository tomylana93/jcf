<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use App\Notifications\Account\EmailAddressChanged;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

final readonly class PrepareEmailChange
{
    /**
     * Before saving a changed email, drop reset tokens issued to the old address, warn that address, and require verification again.
     */
    public function handle(User $user): void
    {
        if (! $user->isDirty('email')) {
            return;
        }

        $previousIdentity = clone $user;
        $previousIdentity->email = $user->getOriginal('email');
        Password::broker()->deleteToken($previousIdentity);
        $user->email_verified_at = null;
        Notification::route('mail', $previousIdentity->email)->notify(new EmailAddressChanged($user->name));
    }
}
