<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Models\ChatConversation;
use Illuminate\Foundation\Http\FormRequest;

/** @method array{typing: bool} validated() */
final class ChatTypingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $conversation instanceof ChatConversation && ($this->user()?->can('sendMessage', $conversation) ?? false);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['typing' => ['required', 'boolean']];
    }
}
