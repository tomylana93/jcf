<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string|null $actor_id
 * @property string $actor_name
 * @property string $event
 * @property string $subject_type
 * @property string $subject_id
 * @property string $subject_label
 * @property array<string, mixed> $before
 * @property array<string, mixed> $after
 * @property Carbon $created_at
 */
#[Fillable(['actor_id', 'actor_name', 'event', 'subject_type', 'subject_id', 'subject_label', 'before', 'after'])]
final class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory, HasUuids, MassPrunable;

    /** @return Builder<self> */
    public function prunable(): Builder
    {
        return self::query()->where('created_at', '<=', now()->subDays(max(1, (int) config('audit.retention_days', 365))));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array'];
    }
}
