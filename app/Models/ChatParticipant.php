<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ChatParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $conversation_id
 * @property string $user_id
 * @property int $last_read_sequence
 */
#[Fillable(['conversation_id', 'user_id', 'last_read_sequence'])]
final class ChatParticipant extends Model
{
    /** @use HasFactory<ChatParticipantFactory> */
    use HasFactory, HasUuids;

    /** @return BelongsTo<ChatConversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['last_read_sequence' => 'integer'];
    }
}
