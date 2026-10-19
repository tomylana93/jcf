<?php

declare(strict_types=1);

namespace App\Http\Resources\Website;

use App\Models\WebsiteContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WebsiteContent */
final class WebsiteArticleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'title' => $this->draft['title'], 'slug' => $this->draft['slug'], 'published' => $this->publication_version !== null, 'published_at' => $this->published_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String()];
    }
}
