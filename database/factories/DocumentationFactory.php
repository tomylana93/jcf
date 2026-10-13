<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Documentation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Documentation> */
final class DocumentationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);
        $text = fake()->paragraph();

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => fake()->sentence(),
            'content_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]]]],
            'content_html' => '<p>'.e($text).'</p>',
            'content_text' => $text,
            'status' => 'draft',
            'author_id' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => 'published', 'published_at' => now()]);
    }
}
