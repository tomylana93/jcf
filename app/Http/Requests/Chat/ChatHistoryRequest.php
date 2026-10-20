<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Models\ChatConversation;
use Illuminate\Foundation\Http\FormRequest;

final class ChatHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $conversation instanceof ChatConversation && ($this->user()?->can('view', $conversation) ?? false);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'before' => ['nullable', 'integer', 'min:1', 'max:2147483647', 'prohibits:after'],
            'after' => ['nullable', 'integer', 'min:0', 'max:2147483647', 'prohibits:before'],
        ];
    }
}
