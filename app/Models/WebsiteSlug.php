<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WebsiteSlugFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $slug
 * @property string $website_content_id
 * @property bool $was_published
 */
#[Fillable(['slug', 'website_content_id', 'was_published'])]
final class WebsiteSlug extends Model
{
    /** @use HasFactory<WebsiteSlugFactory> */
    use HasFactory;

    use HasUuids;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['was_published' => 'boolean'];
    }
}
