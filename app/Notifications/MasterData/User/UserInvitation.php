<?php

declare(strict_types=1);

namespace App\Notifications\MasterData\User;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

final class UserInvitation extends ResetPassword
{
    /** @param User $notifiable */
    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('user.invitation.subject'))
            ->line(__('user.invitation.description'))
            ->action(__('user.invitation.action'), $this->resetUrl($notifiable))
            ->line(__('user.invitation.expires', ['minutes' => config('auth.passwords.users.expire')]))
            ->line(__('user.invitation.verification'));
    }
}
