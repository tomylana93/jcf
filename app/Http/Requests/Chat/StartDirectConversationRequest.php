<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Models\ChatConversation;
use Illuminate\Foundation\Http\FormRequest;

/** @method array{recipient_id: string} validated() */
final class StartDirectConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ChatConversation::class) ?? false;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['recipient_id' => ['required', 'uuid', 'exists:users,id']];
    }
}
