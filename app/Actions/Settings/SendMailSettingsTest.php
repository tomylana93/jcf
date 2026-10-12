<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Settings\MailSettingsTestMessage;
use Illuminate\Support\Facades\Mail;

final class SendMailSettingsTest
{
    public function handle(string $recipient): void
    {
        Mail::to($recipient)->send(new MailSettingsTestMessage);
    }
}
