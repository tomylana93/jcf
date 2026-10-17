<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WebsiteContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $id
 * @property string|null $page_key
 * @property array<string, mixed> $draft
 * @property int $draft_version
 * @property array<string, mixed>|null $published
 * @property string|null $publication_version
 * @property string|null $published_slug
 * @property Carbon|null $published_at
 * @property string|null $author_id
 * @property string|null $updated_by
 */
#[Fillable(['page_key', 'draft', 'draft_version', 'published', 'publication_version', 'published_slug', 'published_at', 'author_id', 'updated_by'])]
#[Hidden(['draft', 'author_id', 'updated_by'])]
final class WebsiteContent extends Model implements HasMedia
{
    /** @use HasFactory<WebsiteContentFactory> */
    use HasFactory, HasUuids, InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('website-images')->useDisk('local')->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['draft' => 'array', 'published' => 'array', 'draft_version' => 'integer', 'published_at' => 'datetime'];
    }
}
