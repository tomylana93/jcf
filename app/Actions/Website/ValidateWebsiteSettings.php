<?php

declare(strict_types=1);

namespace App\Actions\Website;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final readonly class ValidateWebsiteSettings
{
    public function __construct(private ValidateWebsiteContent $validateContent) {}

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function handle(array $data): array
    {
        $link = ['array:label,href'];
        $rules = [
            'settings' => ['required', 'array:preset,identity,seo,contact,navigation,footer,preferences'],
            'settings.preset' => ['sometimes', Rule::in(['corporate', 'editorial', 'minimal'])],
            'settings.identity' => ['sometimes', 'string', 'max:255'],
            'settings.seo' => ['sometimes', 'array:title,description,robots', 'required_array_keys:title,description,robots'],
            'settings.seo.title' => ['required_with:settings.seo', 'string', 'max:255'],
            'settings.seo.description' => ['present_with:settings.seo', 'string', 'max:500'],
            'settings.seo.robots' => ['required_with:settings.seo', Rule::in(['index,follow', 'noindex,nofollow'])],
            'settings.contact' => ['sometimes', 'array:email,phone,address,hours,location_url,social_links', 'required_array_keys:email,phone,address,hours,location_url,social_links'],
            'settings.contact.email' => ['nullable', 'email', 'max:255'],
            'settings.contact.phone' => ['nullable', 'string', 'max:100'],
            'settings.contact.address' => ['sometimes', 'string', 'max:2000'],
            'settings.contact.hours' => ['sometimes', 'string', 'max:2000'],
            'settings.contact.location_url' => ['nullable', 'url:http,https', 'max:2000'],
            'settings.contact.social_links' => ['sometimes', 'array', 'list', 'max:10'],
            'settings.contact.social_links.*' => $link,
            'settings.contact.social_links.*.label' => ['required', 'string', 'max:100'],
            'settings.contact.social_links.*.href' => ['required', 'url:http,https', 'max:2000'],
            'settings.navigation' => ['sometimes', 'array', 'list', 'max:10'],
            'settings.navigation.*' => $link,
            'settings.navigation.*.label' => ['required', 'string', 'max:100'],
            'settings.navigation.*.href' => ['required', 'string', 'max:2000', $this->validateContent->urlRule()],
            'settings.footer' => ['sometimes', 'array:text,links', 'required_array_keys:text,links'],
            'settings.footer.text' => ['sometimes', 'string', 'max:2000'],
            'settings.footer.links' => ['sometimes', 'array', 'list', 'max:10'],
            'settings.footer.links.*' => $link,
            'settings.footer.links.*.label' => ['required', 'string', 'max:100'],
            'settings.footer.links.*.href' => ['required', 'string', 'max:2000', $this->validateContent->urlRule()],
            'settings.preferences' => ['sometimes', 'array:corporate,editorial,minimal'],
            'settings.preferences.*' => ['array:sectionOrder,hiddenSections', 'required_array_keys:sectionOrder,hiddenSections'],
            'settings.preferences.*.sectionOrder' => ['present', 'array', 'list', 'max:20'],
            'settings.preferences.*.sectionOrder.*' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/', 'distinct:strict'],
            'settings.preferences.*.hiddenSections' => ['present', 'array', 'list', 'max:20'],
            'settings.preferences.*.hiddenSections.*' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/', 'distinct:strict'],
        ];

        return Validator::make(['settings' => $data], $rules)->validate()['settings'];

    }
}
