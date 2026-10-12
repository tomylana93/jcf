<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Settings;

final class RecordSettingsChange
{
    public function __construct(public RecordAuditLog $recordAuditLog) {}

    /** @param Closure(): void $update */
    public function handle(Settings $settings, ?User $actor, Closure $update): void
    {
        DB::transaction(function () use ($settings, $actor, $update): void {
            $before = $settings->toArray();
            $update();
            $after = $settings->toArray();

            if (($before['smtp_password'] ?? null) !== ($after['smtp_password'] ?? null)) {
                $before['smtp_password_changed'] = false;
                $after['smtp_password_changed'] = true;
            }

            unset($before['smtp_password'], $after['smtp_password']);

            $this->recordAuditLog->handle($actor, 'settings.updated', 'settings', $settings::group(), $settings::group(), $before, $after);
        });
    }
}
