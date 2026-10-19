<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Actions\Audit\RecordAuditLog;
use App\Models\User;
use App\Models\WebsiteContent;
use App\Models\WebsiteSlug;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class PublishWebsiteContent
{
    public function __construct(private ValidateWebsiteContent $validateContent, private InvalidateWebsiteCache $invalidate, private RecordAuditLog $audit) {}

    public function handle(User $actor, WebsiteContent $content, int $expectedVersion, ?string $publicationDate = null): WebsiteContent
    {
        Gate::forUser($actor)->authorize('publish', $content);
        Validator::make(['date' => $publicationDate], ['date' => ['nullable', 'date']])->validate();

        return DB::transaction(function () use ($actor, $content, $expectedVersion, $publicationDate): WebsiteContent {
            $record = WebsiteContent::query()->lockForUpdate()->findOrFail($content->id);
            if ($record->draft_version !== $expectedVersion) {
                throw ValidationException::withMessages(['version' => __('website.error.stale_draft')]);
            }
            $draft = Arr::only($record->draft, ['title', 'slug', 'excerpt', 'body_json', 'sections', 'seo', 'cover', 'featured']);
            foreach ($draft['sections'] as &$section) {
                unset($section['body']);
            }
            unset($section);
            $validated = $this->validateContent->handle($draft, $record, $record->page_key === null);
            $snapshot = Arr::only($validated, ['title', 'slug', 'excerpt', 'body_html', 'body_text', 'sections', 'seo', 'cover', 'featured']);
            foreach ($snapshot['sections'] as &$section) {
                unset($section['body_json']);
            }
            unset($section);
            $snapshot['author'] = User::query()->find($record->author_id)?->name;
            $snapshot['kind'] = $record->page_key ?? 'article';
            $before = clone $record;
            if ($record->page_key === null) {
                $slug = WebsiteSlug::query()->firstOrCreate(['slug' => $validated['slug']], ['website_content_id' => $record->id]);
                if ($slug->website_content_id !== $record->id) {
                    throw ValidationException::withMessages(['slug' => __('website.error.slug_taken')]);
                }
                $slug->update(['was_published' => true]);
            }
            $record->fill(['published' => $snapshot, 'published_slug' => $validated['slug'] ?? null, 'published_at' => $publicationDate ?? now(), 'publication_version' => (string) Str::uuid()])->save();
            $this->audit->handle($actor, 'website.published', WebsiteContent::class, $record->id, $validated['title'], ['publication_version' => $before->publication_version], ['publication_version' => $record->publication_version]);
            $this->invalidate->handle($before);

            return $record;
        });
    }
}
