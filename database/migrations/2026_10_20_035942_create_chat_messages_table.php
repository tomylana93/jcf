<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->foreignUuid('sender_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('sequence');
            $table->uuid('client_message_id');
            $table->text('body');
            $table->timestamps();

            $table->unique(['conversation_id', 'sequence']);
            $table->unique(['sender_id', 'client_message_id']);
            $table->foreign(['conversation_id', 'sender_id'])
                ->references(['conversation_id', 'user_id'])->on('chat_participants')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
