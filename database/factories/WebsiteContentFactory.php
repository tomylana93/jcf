<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\WebsiteContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WebsiteContent> */
final class WebsiteContentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'page_key' => null,
            'draft' => ['title' => fake()->sentence(), 'slug' => fake()->unique()->slug(), 'excerpt' => '', 'body_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]], 'sections' => [], 'seo' => [], 'cover' => null],
            'author_id' => User::factory(),
        ];
    }
}
