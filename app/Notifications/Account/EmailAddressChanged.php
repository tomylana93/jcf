<?php

declare(strict_types=1);

namespace App\Notifications\Account;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Warns the previous address of an account that its login email was changed.
 */
final class EmailAddressChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $accountName)
    {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('account.email_changed.subject'))
            ->line(__('account.email_changed.description', ['name' => $this->accountName]))
            ->line(__('account.email_changed.warning'));
    }
}
