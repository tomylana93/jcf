<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ContactMessageReceived extends Notification
{
    public function __construct(public string $messageId) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('contact.title.notification'))
            ->line(__('contact.helper.notification'))
            ->action(__('contact.button.open'), route('website.inbox.show', ['message' => $this->messageId]));
    }
}
