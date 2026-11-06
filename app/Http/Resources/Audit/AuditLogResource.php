<?php

declare(strict_types=1);

namespace App\Http\Resources\Audit;

use App\Actions\Audit\DescribeAuditChanges;
use App\Enums\AuditEvent;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AuditLog */
final class AuditLogResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'actor_name' => $this->actor_name === 'System' && $this->actor_id === null ? __('audit.label.system') : $this->actor_name,
            'event' => $this->event,
            'event_label' => AuditEvent::tryFrom($this->event)?->label() ?? $this->event,
            'subject_label' => $this->subject_type === 'settings' ? __('audit.settings.'.$this->subject_label) : $this->subject_label,
            'changes' => resolve(DescribeAuditChanges::class)->handle($this->resource),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
