<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Actions\Audit\RecordAuditLog;
use App\Models\User;
use App\Models\WebsiteContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class UnpublishWebsiteContent
{
    public function __construct(private InvalidateWebsiteCache $invalidate, private RecordAuditLog $audit) {}

    public function handle(User $actor, WebsiteContent $content): WebsiteContent
    {
        Gate::forUser($actor)->authorize('publish', $content);

        return DB::transaction(function () use ($actor, $content): WebsiteContent {
            $record = WebsiteContent::query()->lockForUpdate()->findOrFail($content->id);
            $before = clone $record;
            $record->fill(['published' => null, 'published_at' => null, 'published_slug' => null, 'publication_version' => null])->save();
            $this->audit->handle($actor, 'website.unpublished', WebsiteContent::class, $record->id, $record->draft['title'], ['publication_version' => $before->publication_version], ['publication_version' => null]);
            $this->invalidate->handle($before);

            return $record;
        });
    }
}
