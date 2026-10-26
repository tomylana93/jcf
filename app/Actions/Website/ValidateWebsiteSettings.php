<?php

declare(strict_types=1);

namespace App\Actions\Website;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final readonly class ValidateWebsiteSettings
{
    public function __construct(private ValidateWebsiteContent $validateContent) {}

    /**
     * Validate settings on clean attribute paths so field errors surface without prefixes.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handle(array $data): array
    {
        // The wrapper rejects unknown settings fields, the flat rules report errors on clean paths.
        Validator::make(['settings' => $data], ['settings' => ['required', 'array:public_pages_enabled,preset,identity,seo,contact,navigation,footer,preferences']])->validate();
        $link = ['array:label,href'];
        $rules = [
            'public_pages_enabled' => ['sometimes', 'required', 'boolean'],
            'preset' => ['sometimes', Rule::in(['corporate', 'editorial', 'minimal'])],
            'identity' => ['sometimes', 'string', 'max:255'],
            'seo' => ['sometimes', 'array:title,description,robots', 'required_array_keys:title,description,robots'],
            'seo.title' => ['present_with:seo', 'string', 'max:255'],
            'seo.description' => ['present_with:seo', 'string', 'max:500'],
            'seo.robots' => ['required_with:seo', Rule::in(['index,follow', 'noindex,nofollow'])],
            'contact' => ['sometimes', 'array:email,phone,address,hours,location_url,social_links', 'required_array_keys:email,phone,address,hours,location_url,social_links'],
            'contact.email' => ['nullable', 'email', 'max:255'],
            'contact.phone' => ['nullable', 'string', 'max:100'],
            'contact.address' => ['nullable', 'string', 'max:2000'],
            'contact.hours' => ['nullable', 'string', 'max:2000'],
            'contact.location_url' => ['nullable', 'url:http,https', 'max:2000'],
            'contact.social_links' => ['sometimes', 'array', 'list', 'max:10'],
            'contact.social_links.*' => $link,
            'contact.social_links.*.label' => ['required', 'string', 'max:100'],
            'contact.social_links.*.href' => ['required', 'url:http,https', 'max:2000'],
            'navigation' => ['sometimes', 'array', 'list', 'max:10'],
            'navigation.*' => $link,
            'navigation.*.label' => ['required', 'string', 'max:100'],
            'navigation.*.href' => ['required', 'string', 'max:2000', $this->validateContent->urlRule()],
            'footer' => ['sometimes', 'array:text,links', 'required_array_keys:text,links'],
            'footer.text' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'footer.links' => ['sometimes', 'array', 'list', 'max:10'],
            'footer.links.*' => $link,
            'footer.links.*.label' => ['required', 'string', 'max:100'],
            'footer.links.*.href' => ['required', 'string', 'max:2000', $this->validateContent->urlRule()],
            'preferences' => ['sometimes', 'array:corporate,editorial,minimal'],
            'preferences.*' => ['array:sectionOrder,hiddenSections', 'required_array_keys:sectionOrder,hiddenSections'],
            'preferences.*.sectionOrder' => ['present', 'array', 'list', 'max:20'],
            'preferences.*.sectionOrder.*' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/', 'distinct:strict'],
            'preferences.*.hiddenSections' => ['present', 'array', 'list', 'max:20'],
            'preferences.*.hiddenSections.*' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/', 'distinct:strict'],
        ];

        return Validator::make($data, $rules)->validate();
    }
}
