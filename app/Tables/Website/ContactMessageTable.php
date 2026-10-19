<?php

declare(strict_types=1);

namespace App\Tables\Website;

use App\Models\ContactMessage;
use App\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** @extends Table<ContactMessage> */
final class ContactMessageTable extends Table
{
    /** @return Builder<ContactMessage> */
    protected function query(): Builder
    {
        return ContactMessage::query()->select(['id', 'subject', 'created_at', 'handled_at']);
    }

    /** @return array<int, string> */
    protected function allowedFilters(): array
    {
        return [];
    }

    /** @return array<int, string> */
    protected function allowedSorts(): array
    {
        return ['created_at', 'handled_at'];
    }

    /** @return list<string> */
    protected function defaultSort(): array
    {
        return ['-created_at', 'id'];
    }
}
