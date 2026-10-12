<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AuditLog> */
final class AuditLogFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'actor_id' => null,
            'actor_name' => 'System',
            'event' => 'user.updated',
            'subject_type' => 'user',
            'subject_id' => (string) Str::uuid(),
            'subject_label' => fake()->name(),
            'before' => ['name' => 'Previous name'],
            'after' => ['name' => 'Updated name'],
        ];
    }
}
