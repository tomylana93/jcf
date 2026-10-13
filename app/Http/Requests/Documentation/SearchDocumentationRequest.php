<?php

declare(strict_types=1);

namespace App\Http\Requests\Documentation;

use Illuminate\Foundation\Http\FormRequest;

final class SearchDocumentationRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:200'], 'page' => ['sometimes', 'integer', 'min:1']];
    }
}
