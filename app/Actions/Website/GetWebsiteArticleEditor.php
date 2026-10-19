<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Models\User;
use App\Models\WebsiteContent;
use Illuminate\Support\Arr;

final readonly class GetWebsiteArticleEditor
{
    public function __construct(private ListWebsiteImages $images) {}

    /** @return array<string, mixed> */
    public function handle(?WebsiteContent $article): array
    {
        return [
            'id' => $article?->id,
            'content' => $article === null ? null : [...Arr::except($article->draft, ['body_html', 'body_text']), 'featured' => (bool) ($article->draft['featured'] ?? false), 'excerpt' => $article->draft['excerpt'] ?? '', 'version' => $article->draft_version, 'author_id' => $article->author_id],
            'published' => $article?->published !== null,
            'published_at' => $article?->published_at?->toIso8601String(),
            'images' => $article === null ? [] : $this->images->handle($article),
            'authors' => User::query()->orderBy('name')->get(['id', 'name'])->map(static fn (User $user): array => ['value' => $user->id, 'label' => $user->name])->all(),
        ];
    }
}
