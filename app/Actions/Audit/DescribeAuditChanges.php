<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Enums\ApiClientStatus;
use App\Enums\ApiTokenAbility;
use App\Enums\ApiTokenExpiration;
use App\Enums\AppLayout;
use App\Enums\AuditEvent;
use App\Enums\AuthLayout;
use App\Enums\BrandColor;
use App\Enums\LogoType;
use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use BackedEnum;

/**
 * Turns stored audit values into labelled, display-ready rows. Unknown fields fall back to their raw name
 * and unmapped structured values to JSON, so older or custom audit entries remain readable.
 */
final readonly class DescribeAuditChanges
{
    /** Settings groups recorded by module toggles, keyed by group with their module label key. */
    private const array MODULE_GROUPS = ['chat' => 'chat', 'documentation' => 'documentation', 'backup' => 'backup', 'api' => 'api'];

    /** @var array<string, class-string<BackedEnum>> */
    private const array ENUM_FIELDS = [
        'logo_type' => LogoType::class,
        'color' => BrandColor::class,
        'app_layout' => AppLayout::class,
        'auth_layout' => AuthLayout::class,
        'abilities' => ApiTokenAbility::class,
        'expiration' => ApiTokenExpiration::class,
        'permissions' => Permission::class,
        'role' => Role::class,
    ];

    /** @return list<array{field: string, label: string, before: string|null, after: string|null}> */
    public function handle(AuditLog $log): array
    {
        $fields = array_values(array_unique([...array_keys($log->before), ...array_keys($log->after)]));

        return array_map(function (string $field) use ($log): array {
            $changeFlag = str_ends_with($field, '_changed');

            return [
                'field' => $field,
                'label' => $this->label($log, $changeFlag ? mb_substr($field, 0, -mb_strlen('_changed')) : $field),
                'before' => $changeFlag ? null : $this->value($log, $field, $log->before[$field] ?? null),
                'after' => $changeFlag ? $this->translation('audit.label.changed') : $this->value($log, $field, $log->after[$field] ?? null),
            ];
        }, $fields);
    }

    private function label(AuditLog $log, string $field): string
    {
        $base = str_ends_with($field, '_path') ? mb_substr($field, 0, -mb_strlen('_path')) : $field;

        $candidates = match ($log->subject_type) {
            'settings' => array_key_exists($log->subject_label, self::MODULE_GROUPS) && $field === 'enabled'
                ? ['settings.modules.label.'.self::MODULE_GROUPS[$log->subject_label]]
                : ["settings.{$log->subject_label}.label.{$field}", "settings.{$log->subject_label}.label.{$base}"],
            'user' => $field === 'permissions' ? ['permission.label.direct'] : ["user.label.{$field}"],
            'api_client' => in_array($log->event, [AuditEvent::ApiClientTokenIssued->value, AuditEvent::ApiClientTokenRevoked->value], true)
                ? ["account.api_tokens.label.{$field}"]
                : ["api_client.label.{$field}"],
            default => [],
        };

        foreach ($candidates as $key) {
            $label = $this->translation($key);

            if ($label !== null) {
                return $label;
            }
        }

        return $field;
    }

    private function value(AuditLog $log, string $field, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $this->translation($value ? 'audit.label.yes' : 'audit.label.no');
        }

        if (is_array($value) && array_is_list($value) && array_filter($value, is_string(...)) === $value) {
            return implode(', ', array_map(fn (string $item): string => $this->scalar($log, $field, $item), $value));
        }

        if (is_string($value)) {
            return $this->scalar($log, $field, $value);
        }

        return is_scalar($value) ? (string) $value : (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function scalar(AuditLog $log, string $field, string $value): string
    {
        $enum = match (true) {
            $field === 'status' && $log->subject_type === 'user' => UserStatus::class,
            $field === 'status' && $log->subject_type === 'api_client' => ApiClientStatus::class,
            default => self::ENUM_FIELDS[$field] ?? null,
        };

        if ($enum !== null) {
            $case = $enum::tryFrom($value);

            return $case?->label() ?? $value;
        }

        if ($log->subject_type === 'settings' && $log->subject_label === 'mail' && in_array($field, ['mailer', 'smtp_scheme'], true)) {
            return $this->translation('settings.mail.option.'.$value) ?? $value;
        }

        return $value;
    }

    /** Returns the translation for a key, or null when the key has no translation. */
    private function translation(string $key): ?string
    {
        $text = __($key);

        return is_string($text) && $text !== $key ? $text : null;
    }
}
