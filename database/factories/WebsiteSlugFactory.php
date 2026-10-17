<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WebsiteContent;
use App\Models\WebsiteSlug;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WebsiteSlug> */
final class WebsiteSlugFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['slug' => fake()->unique()->slug(), 'website_content_id' => WebsiteContent::factory()];
    }
}
