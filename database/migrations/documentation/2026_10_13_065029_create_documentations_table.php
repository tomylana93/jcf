<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->jsonb('content_json');
            $table->text('content_html');
            $table->text('content_text');
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->string('status')->default('draft');
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at']);
        });

        DB::statement("ALTER TABLE documentations ADD COLUMN search_vector tsvector GENERATED ALWAYS AS (setweight(to_tsvector('simple', coalesce(title, '')), 'A') || setweight(to_tsvector('simple', coalesce(summary, '')), 'B') || setweight(to_tsvector('simple', content_text), 'C')) STORED");
        DB::statement('CREATE INDEX documentations_search_index ON documentations USING GIN (search_vector)');
    }

    public function down(): void
    {
        Schema::dropIfExists('documentations');
    }
};
