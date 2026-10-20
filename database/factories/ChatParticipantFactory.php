<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ChatConversation;
use App\Models\ChatParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ChatParticipant> */
final class ChatParticipantFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'conversation_id' => ChatConversation::factory(),
            'user_id' => User::factory(),
            'last_read_sequence' => 0,
        ];
    }
}
