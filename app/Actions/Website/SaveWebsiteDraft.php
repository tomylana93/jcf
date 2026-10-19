<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Actions\Audit\RecordAuditLog;
use App\Enums\WebsitePage;
use App\Models\User;
use App\Models\WebsiteContent;
use App\Models\WebsiteSlug;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class SaveWebsiteDraft
{
    public function __construct(private ValidateWebsiteContent $validateContent, private RecordAuditLog $audit) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data, ?WebsiteContent $content = null, ?int $expectedVersion = null, ?string $pageKey = null, ?string $authorId = null): WebsiteContent
    {
        Gate::forUser($actor)->authorize($content === null ? 'create' : 'update', $content ?? WebsiteContent::class);
        Validator::make(['page_key' => $pageKey, 'author_id' => $authorId], ['author_id' => ['nullable', 'uuid', 'exists:users,id'], 'page_key' => ['nullable', Rule::enum(WebsitePage::class)]])->validate();
        try {
            return DB::transaction(function () use ($actor, $data, $content, $expectedVersion, $pageKey, $authorId): WebsiteContent {
                $record = $content === null ? new WebsiteContent(['page_key' => $pageKey, 'author_id' => $actor->id]) : WebsiteContent::query()->lockForUpdate()->findOrFail($content->id);
                if ($content !== null && $record->draft_version !== $expectedVersion) {
                    throw ValidationException::withMessages(['version' => __('website.error.stale_draft')]);
                }
                $validated = $this->validateContent->handle($data, $record, $record->page_key === null);
                if ($authorId !== null) {
                    $record->author_id = $authorId;
                }
                $record->fill(['draft' => $validated, 'draft_version' => $content === null ? 1 : $record->draft_version + 1, 'updated_by' => $actor->id]);
                $record->save();
                if ($record->page_key === null) {
                    $slug = WebsiteSlug::query()->firstOrCreate(['slug' => $validated['slug']], ['website_content_id' => $record->id]);
                    if ($slug->website_content_id !== $record->id) {
                        throw ValidationException::withMessages(['slug' => __('website.error.slug_taken')]);
                    }
                    WebsiteSlug::query()->where('website_content_id', $record->id)->where('was_published', false)->where('slug', '!=', $validated['slug'])->delete();
                }
                $this->audit->handle($actor, 'website.draft.saved', WebsiteContent::class, $record->id, $validated['title'], [], ['draft_version' => $record->draft_version]);

                return $record;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['slug' => __('website.error.slug_taken')]);
        }
    }
}
