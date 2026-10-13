<?php

declare(strict_types=1);

namespace App\Http\Requests\Documentation;

use App\Models\Documentation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** @method array{title: string, slug: string, summary?: string|null, content_json: array<string, mixed>, status: string} validated() */
final class SaveDocumentationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $documentation = $this->route('documentation');

        return $documentation instanceof Documentation
            ? ($this->user()?->can('update', $documentation) ?? false)
            : ($this->user()?->can('create', Documentation::class) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $documentation = $this->route('documentation');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::notIn(['manage']), Rule::unique('documentations', 'slug')->ignore($documentation instanceof Documentation ? $documentation : null)],
            'summary' => ['nullable', 'string', 'max:2000'],
            'content_json' => ['required', 'array'],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $title = $this->input('title');

        if ($this->isNotFilled('slug') && is_string($title)) {
            $this->merge(['slug' => Str::slug($title)]);
        }
    }
}
