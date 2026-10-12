<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AvatarSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'temporary_avatar' => ['bail', Rule::requiredIf($this->routeIs('profile.avatar.discard') || ! $this->boolean('remove_avatar')),
                'nullable', 'prohibited_if:remove_avatar,true', 'uuid',
                Rule::exists('media', 'uuid')->where('model_type', $this->user()->getMorphClass())
                    ->where('model_id', $this->user()->id)->where('collection_name', 'avatar_pending')
                    ->where(fn (Builder $query) => $query->where('created_at', '>', now()->subDay())),
            ],
            'remove_avatar' => ['sometimes', 'boolean', Rule::when($this->routeIs('profile.avatar.discard'), ['declined'])],
        ];
    }
}
