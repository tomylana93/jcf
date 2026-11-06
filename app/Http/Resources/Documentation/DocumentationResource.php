<?php

declare(strict_types=1);

namespace App\Http\Resources\Documentation;

use App\Models\Documentation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes a documentation record; the readable and editable variants add the rendered HTML or the editor JSON.
 *
 * @mixin Documentation
 */
final class DocumentationResource extends JsonResource
{
    /** @var 'content_html'|'content_json'|null */
    private ?string $contentAttribute = null;

    public static function readable(Documentation $documentation): self
    {
        return new self($documentation)->withContent('content_html');
    }

    public static function editable(Documentation $documentation): self
    {
        return new self($documentation)->withContent('content_json');
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $record = [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'status' => $this->status,
        ];

        return match ($this->contentAttribute) {
            'content_html' => [...$record, 'content_html' => $this->content_html],
            'content_json' => [...$record, 'content_json' => $this->content_json],
            null => $record,
        };
    }

    /** @param 'content_html'|'content_json' $attribute */
    private function withContent(string $attribute): self
    {
        $this->contentAttribute = $attribute;

        return $this;
    }
}
