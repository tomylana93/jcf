<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Actions\Chat\MarkChatConversationRead;
use App\Models\ChatConversation;
use Illuminate\Foundation\Http\FormRequest;

/** @method array{sequence: int|numeric-string} validated() */
final class MarkChatConversationReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $conversation instanceof ChatConversation && ($this->user()?->can('markRead', $conversation) ?? false);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $conversation = $this->route('conversation');
        abort_unless($conversation instanceof ChatConversation, 404);

        return MarkChatConversationRead::rules($conversation);
    }
}
