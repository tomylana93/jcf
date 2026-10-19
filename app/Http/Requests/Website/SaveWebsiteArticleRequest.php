<?php

declare(strict_types=1);

namespace App\Http\Requests\Website;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

final class SaveWebsiteArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::EditWebsiteContent) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:180'],
            'excerpt' => ['present', 'nullable', 'string', 'max:2000'],
            'body_json' => ['required', 'array'],
            'sections' => ['present', 'array', 'max:0'],
            'seo' => ['present', 'array:title,description,robots,image'],
            'cover' => ['present', 'nullable', 'array:media_id,alt'],
            'featured' => ['required', 'boolean'],
            'author_id' => ['required', 'uuid', 'exists:users,id'],
            'version' => ['required', 'integer', 'min:0'],
        ];
    }
}
