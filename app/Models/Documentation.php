<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DocumentationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $title
 * @property string $slug
 * @property string|null $summary
 * @property array<string, mixed> $content_json
 * @property string $content_html
 * @property string $content_text
 * @property int $schema_version
 * @property string $status
 * @property string|null $author_id
 * @property string|null $updated_by
 * @property Carbon|null $published_at
 */
#[Fillable(['title', 'slug', 'summary', 'content_json', 'content_html', 'content_text', 'schema_version', 'status', 'author_id', 'updated_by', 'published_at'])]
final class Documentation extends Model
{
    /** @use HasFactory<DocumentationFactory> */
    use HasFactory, HasUuids;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['content_json' => 'array', 'schema_version' => 'integer', 'published_at' => 'datetime'];
    }
}
