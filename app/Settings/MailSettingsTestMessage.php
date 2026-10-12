<?php

declare(strict_types=1);

namespace App\Settings;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class MailSettingsTestMessage extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: __('settings.mail.test.subject'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.settings-test');
    }
}
