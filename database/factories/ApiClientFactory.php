<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ApiClientStatus;
use App\Models\ApiClient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiClient>
 */
final class ApiClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'description' => fake()->sentence(),
            'status' => ApiClientStatus::Active,
        ];
    }

    /**
     * Indicate that the client is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (): array => ['status' => ApiClientStatus::Disabled]);
    }
}
