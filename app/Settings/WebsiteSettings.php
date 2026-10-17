<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class WebsiteSettings extends Settings
{
    public string $preset;

    public string $identity;

    /** @phpstan-var array{title: string, description: string, robots: string} */
    public array $seo;

    /** @phpstan-var array{email: string|null, phone: string|null, address: string, hours: string, location_url: string|null, social_links: list<array{label: string, href: string}>} */
    public array $contact;

    /** @phpstan-var list<array{label: string, href: string}> */
    public array $navigation;

    /** @phpstan-var array{text: string, links: list<array{label: string, href: string}>} */
    public array $footer;

    /** @phpstan-var array<string, array{sectionOrder: list<string>, hiddenSections: list<string>}> */
    public array $preferences;

    public string $public_version;

    public static function group(): string
    {
        return 'website';
    }
}
