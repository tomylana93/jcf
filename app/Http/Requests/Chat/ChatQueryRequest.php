<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Models\ChatConversation;
use Illuminate\Foundation\Http\FormRequest;

final class ChatQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ChatConversation::class) ?? false;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:100'], 'cursor' => ['nullable', 'string', 'max:1024']];
    }
}
