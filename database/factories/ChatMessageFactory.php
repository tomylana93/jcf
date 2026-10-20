<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ChatMessage> */
final class ChatMessageFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'conversation_id' => ChatConversation::factory(),
            'sender_id' => fn (array $attributes): string => ChatParticipant::factory()->create([
                'conversation_id' => $attributes['conversation_id'],
            ])->user_id,
            'sequence' => 1,
            'client_message_id' => (string) Str::uuid(),
            'body' => fake()->sentence(),
        ];
    }
}
