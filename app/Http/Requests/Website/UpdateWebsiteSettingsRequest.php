<?php

declare(strict_types=1);

namespace App\Http\Requests\Website;

use App\Enums\WebsitePreset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

final class UpdateWebsiteSettingsRequest extends FormRequest
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
            'public_pages_enabled' => ['sometimes', 'required', 'boolean'],
            'preset' => ['sometimes', 'required', Rule::enum(WebsitePreset::class)],
            'identity' => ['sometimes', 'string', 'max:255'],
            'seo' => ['sometimes', 'array:title,description,robots'],
            'contact' => ['sometimes', 'array:email,phone,address,hours,location_url,social_links'],
            'navigation' => ['sometimes', 'array', 'list', 'max:10'],
            'footer' => ['sometimes', 'array:text,links'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();
        foreach (['identity', 'seo.title', 'seo.description', 'contact.address', 'contact.hours', 'footer.text'] as $field) {
            if (Arr::has($data, $field) && Arr::get($data, $field) === null) {
                Arr::set($data, $field, '');
            }
        }
        $this->replace($data);
    }
}
