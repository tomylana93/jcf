<?php

declare(strict_types=1);

namespace App\Actions\Documentation;

use App\Models\Documentation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class SaveDocumentation
{
    public function __construct(private RenderEditorContent $renderContent) {}

    /** @param array{title: string, slug: string, summary?: string|null, content_json: array<string, mixed>, status: string} $data */
    public function handle(User $user, array $data, ?Documentation $documentation = null): Documentation
    {
        $rendered = $this->renderContent->handle($data['content_json']);

        return DB::transaction(function () use ($user, $data, $documentation, $rendered): Documentation {
            $record = $documentation ?? new Documentation;
            $record->fill([
                ...$data,
                'content_html' => $rendered['html'],
                'content_text' => $rendered['text'],
                'schema_version' => 1,
                'author_id' => $record->author_id ?? $user->id,
                'updated_by' => $user->id,
                'published_at' => $data['status'] === 'published' ? ($record->published_at ?? now()) : null,
            ]);
            $record->save();

            return $record;
        });
    }
}
