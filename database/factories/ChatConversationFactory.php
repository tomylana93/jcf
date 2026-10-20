<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ChatConversation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ChatConversation> */
final class ChatConversationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $ids = [(string) Str::uuid(), (string) Str::uuid()];
        sort($ids, SORT_STRING);

        return [
            'type' => 'direct',
            'direct_pair_key' => implode(':', $ids),
            'last_message_sequence' => 0,
        ];
    }
}
