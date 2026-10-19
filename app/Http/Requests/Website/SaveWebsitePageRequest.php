<?php

declare(strict_types=1);

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

final class SaveWebsitePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        // Keeps the payload shape; domain validation in the Website Actions owns every semantic rule.
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['present', 'nullable', 'string', 'max:2000'],
            'body_json' => ['required', 'array'],
            'sections' => ['present', 'array', 'list', 'max:20'],
            'seo' => ['present', 'array:title,description,robots,image'],
            'cover' => ['present', 'nullable', 'array:media_id,alt'],
            'version' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @param  string|int|null  $key
     * @param  mixed  $default
     * @return ($key is null ? array<string, mixed> : mixed)
     */
    public function validated($key = null, $default = null): mixed
    {
        // The version marker is editor state, not content.
        return Arr::except(parent::validated($key, $default), 'version');
    }
}
