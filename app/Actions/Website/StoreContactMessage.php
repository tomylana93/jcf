<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Models\ContactMessage;
use App\Notifications\ContactMessageReceived;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

final class StoreContactMessage
{
    /** @param array<string, mixed> $data */
    public function handle(array $data): void
    {
        $message = ContactMessage::query()->create(Arr::only($data, ['name', 'email', 'subject', 'message']));
        $recipient = config('website.contact_notification_email');
        if (! is_string($recipient) || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        try {
            Notification::route('mail', $recipient)->notify(new ContactMessageReceived($message->id));
        } catch (Throwable) {
            Log::warning('Contact inbox notification failed.', ['contact_message_id' => $message->id]);
        }
    }
}
