<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Actions\Chat\ValidateChatMessage;
use App\Models\ChatConversation;
use Illuminate\Foundation\Http\FormRequest;

/** @method array{body: string, client_message_id: string} validated() */
final class SendChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $conversation instanceof ChatConversation && ($this->user()?->can('sendMessage', $conversation) ?? false);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ValidateChatMessage::rules();
    }

    protected function prepareForValidation(): void
    {
        $body = $this->input('body');

        if (is_string($body)) {
            $this->merge(['body' => mb_trim($body)]);
        }
    }
}
