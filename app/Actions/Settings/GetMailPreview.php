<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use Illuminate\Notifications\Messages\MailMessage;

final class GetMailPreview
{
    public function handle(): MailMessage
    {
        return (new MailMessage)
            ->subject(__('settings.mail.preview.subject'))
            ->greeting(__('settings.mail.preview.greeting'))
            ->line(__('settings.mail.preview.body'))
            ->action(__('settings.mail.preview.action'), route('dashboard'));
    }
}
