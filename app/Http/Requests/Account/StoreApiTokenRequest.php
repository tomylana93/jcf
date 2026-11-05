<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Enums\ApiTokenAbility;
use App\Enums\ApiTokenExpiration;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['required', 'string', 'distinct', Rule::enum(ApiTokenAbility::class)],
            'expiration' => ['required', 'string', Rule::enum(ApiTokenExpiration::class)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => __('validation.attributes.token_name')];
    }

    /** @return list<ApiTokenAbility> */
    public function abilities(): array
    {
        return array_values(array_map(
            fn (mixed $ability): ApiTokenAbility => ApiTokenAbility::from((string) $ability),
            (array) $this->validated('abilities'),
        ));
    }

    public function expiration(): ApiTokenExpiration
    {
        return ApiTokenExpiration::from((string) $this->validated('expiration'));
    }
}
