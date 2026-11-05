<?php

declare(strict_types=1);

namespace App\Http\Requests\Notification;

use App\Enums\NotificationFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexNotificationRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['status' => ['nullable', Rule::enum(NotificationFilter::class)]];
    }

    public function filter(): NotificationFilter
    {
        return NotificationFilter::tryFrom($this->string('status')->toString()) ?? NotificationFilter::All;
    }
}
