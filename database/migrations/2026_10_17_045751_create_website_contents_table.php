<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_contents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('page_key')->nullable()->unique();
            $table->jsonb('draft');
            $table->unsignedInteger('draft_version')->default(1);
            $table->jsonb('published')->nullable();
            $table->uuid('publication_version')->nullable();
            $table->string('published_slug')->nullable()->unique();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('website_slugs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->foreignUuid('website_content_id')->constrained('website_contents')->cascadeOnDelete();
            $table->boolean('was_published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_slugs');
        Schema::dropIfExists('website_contents');
    }
};
