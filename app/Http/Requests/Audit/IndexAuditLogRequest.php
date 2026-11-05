<?php

declare(strict_types=1);

namespace App\Http\Requests\Audit;

use App\Enums\AuditEvent;
use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexAuditLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ViewAuditLogs) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'array:search,event'],
            'filter.search' => ['nullable', 'string', 'max:200'],
            'filter.event' => ['nullable', Rule::enum(AuditEvent::class)],
        ];
    }
}
