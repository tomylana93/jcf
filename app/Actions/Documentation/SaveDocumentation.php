<?php

declare(strict_types=1);

namespace App\Actions\Documentation;

use App\Enums\DocumentationStatus;
use App\Models\Documentation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class SaveDocumentation
{
    public function __construct(private RenderEditorContent $renderContent) {}

    /** @param array{title: string, slug: string, summary?: string|null, content_json: array<string, mixed>, status: string} $data */
    public function handle(User $actor, array $data, ?Documentation $documentation = null): Documentation
    {
        $rendered = $this->renderContent->handle($data['content_json']);

        return DB::transaction(function () use ($actor, $data, $documentation, $rendered): Documentation {
            $record = $documentation ?? new Documentation;
            $record->fill([
                ...$data,
                'content_html' => $rendered['html'],
                'content_text' => $rendered['text'],
                'schema_version' => 1,
                'author_id' => $record->author_id ?? $actor->id,
                'updated_by' => $actor->id,
                'published_at' => $data['status'] === DocumentationStatus::Published->value ? ($record->published_at ?? now()) : null,
            ]);
            $record->save();

            return $record;
        });
    }
}
