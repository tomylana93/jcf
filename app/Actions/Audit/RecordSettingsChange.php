<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Enums\AuditEvent;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Settings;

final readonly class RecordSettingsChange
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    /**
     * Run the update and audit the changed values; encrypted properties are recorded only as `<name>_changed` flags.
     *
     * @param  Closure(): void  $update
     */
    public function handle(Settings $settings, ?User $actor, Closure $update): void
    {
        DB::transaction(function () use ($settings, $actor, $update): void {
            $before = $settings->toArray();
            $update();
            $after = $settings->toArray();

            foreach ($settings::encrypted() as $secret) {
                if (($before[$secret] ?? null) !== ($after[$secret] ?? null)) {
                    $before[$secret.'_changed'] = false;
                    $after[$secret.'_changed'] = true;
                }

                unset($before[$secret], $after[$secret]);
            }

            $this->recordAuditLog->handle($actor, AuditEvent::SettingsUpdated, 'settings', $settings::group(), $settings::group(), $before, $after);
        });
    }
}
