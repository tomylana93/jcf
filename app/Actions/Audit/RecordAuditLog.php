<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Models\AuditLog;
use App\Models\User;

final class RecordAuditLog
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function handle(?User $actor, string $event, string $subjectType, string $subjectId, string $subjectLabel, array $before, array $after): ?AuditLog
    {
        $before = $this->redact($before);
        $after = $this->redact($after);

        $changed = array_filter(array_unique([...array_keys($before), ...array_keys($after)]),
            fn (string $key): bool => ($before[$key] ?? null) !== ($after[$key] ?? null));

        if ($changed === []) {
            return null;
        }

        return AuditLog::query()->create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor->name ?? 'System',
            'event' => $event,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'subject_label' => $subjectLabel,
            'before' => array_intersect_key($before, array_flip($changed)),
            'after' => array_intersect_key($after, array_flip($changed)),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function redact(array $values): array
    {
        foreach ($values as $key => $value) {
            if (! in_array($key, ['smtp_password_changed', 'password_min_length'], true) && preg_match('/password|secret|token|credential|authorization|cookie|(?:^|_)key(?:$|_)/i', (string) $key) === 1) {
                unset($values[$key]);
            } elseif (is_array($value)) {
                $values[$key] = $this->redact($value);
            }
        }

        return $values;
    }
}
