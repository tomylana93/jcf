<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Mail\MailSettingsTestMessage;
use Illuminate\Support\Facades\Mail;

final readonly class SendMailSettingsTest
{
    public function handle(string $recipient): void
    {
        Mail::to($recipient)->send(new MailSettingsTestMessage);
    }
}
