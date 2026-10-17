<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = [
            'preset' => 'corporate', 'identity' => '',
            'seo' => ['title' => '', 'description' => '', 'robots' => 'index,follow'],
            'contact' => ['email' => null, 'phone' => null, 'address' => '', 'hours' => '', 'location_url' => null, 'social_links' => []],
            'navigation' => [], 'footer' => ['text' => '', 'links' => []],
            'preferences' => array_fill_keys(['corporate', 'editorial', 'minimal'], ['sectionOrder' => [], 'hiddenSections' => []]),
            'public_version' => (string) Str::uuid(),
        ];
        foreach ($defaults as $key => $value) {
            $this->migrator->add('website.'.$key, $value);
        }
    }

    public function down(): void
    {
        foreach (['preset', 'identity', 'seo', 'contact', 'navigation', 'footer', 'preferences', 'public_version'] as $key) {
            $this->migrator->delete('website.'.$key);
        }
    }
};
