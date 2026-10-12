<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;

final class ApplicationNotification extends Notification
{
    public function __construct(public string $kind, public string $subject = '') {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array{kind: string, subject: string} */
    public function toDatabase(object $notifiable): array
    {
        return ['kind' => $this->kind, 'subject' => $this->subject];
    }
}
