<?php

declare(strict_types=1);

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

final class GlobalSearchRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['query' => ['required', 'string', 'min:2', 'max:200']];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('query'))) {
            $this->merge(['query' => mb_trim($this->input('query'))]);
        }
    }
}
