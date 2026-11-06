<?php

declare(strict_types=1);

namespace App\Actions\System;

use App\Enums\HealthDetail;
use App\Enums\Mailer;
use App\Jobs\SystemHeartbeat;
use App\Settings\MailSettings;
use App\Support\Contracts\HealthCheck;
use App\Support\Contracts\MonitoredQueue;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final readonly class GetSystemHealth
{
    /**
     * @param  iterable<HealthCheck>  $contributedChecks
     * @param  iterable<MonitoredQueue>  $monitoredQueues
     */
    public function __construct(private MailSettings $mailSettings, private iterable $contributedChecks = [], private iterable $monitoredQueues = []) {}

    /** @return list<array{name: string, status: string, detail: string}> */
    public function handle(): array
    {
        $checks = [
            $this->check('database', function (): HealthDetail {
                DB::connection()->getPdo();
                DB::select('SELECT 1');

                return HealthDetail::Reachable;
            }),
            $this->check('storage', function (): HealthDetail {
                $disk = Storage::disk();
                $path = '.health/'.Str::uuid();
                try {
                    if (! $disk->put($path, 'health') || $disk->get($path) !== 'health') {
                        return HealthDetail::Unavailable;
                    }

                    return HealthDetail::Writable;
                } finally {
                    $disk->delete($path);
                }
            }),
            $this->check('cache', function (): HealthDetail {
                $key = 'system.probe.'.Str::uuid();
                try {
                    Cache::put($key, 'health', 30);

                    return Cache::get($key) === 'health' ? HealthDetail::Reachable : HealthDetail::Unavailable;
                } finally {
                    Cache::forget($key);
                }
            }),
            ...$this->queueChecks(),
            $this->check('scheduler', fn (): HealthDetail => $this->heartbeat('system.scheduler_heartbeat', HealthDetail::StaleScheduler)),
            $this->check('mail', function (): HealthDetail {
                $mailer = config('mail.default');

                return ($mailer === 'settings' && $this->mailSettings->mailer === Mailer::Smtp->value && $this->mailSettings->smtp_host && $this->mailSettings->from_address)
                    || ($mailer === 'smtp' && config('mail.mailers.smtp.host') && config('mail.from.address'))
                    ? HealthDetail::Configured : HealthDetail::NotConfigured;
            }),
        ];

        if (app()->isProduction()) {
            $checks[] = $this->check('security', fn (): HealthDetail => $this->productionSafeguards());
        }

        foreach ($this->contributedChecks as $check) {
            $checks[] = $this->check($check->name(), $check->handle(...));
        }

        return $checks;
    }

    /**
     * @param  Closure(): HealthDetail  $probe
     * @return array{name: string, status: string, detail: string}
     */
    private function check(string $name, Closure $probe): array
    {
        try {
            $detail = $probe();
        } catch (Throwable) {
            $detail = HealthDetail::Unavailable;
        }

        return ['name' => $name, 'status' => $detail->status(), 'detail' => $detail->value];
    }

    /**
     * One heartbeat check per queue a worker is expected on; queues of disabled features are omitted rather than reported stale, and an undeterminable feature state reports the queue unavailable.
     *
     * @return list<array{name: string, status: string, detail: string}>
     */
    private function queueChecks(): array
    {
        $checks = [];
        foreach ($this->monitoredQueues as $queue) {
            try {
                if (! $queue->monitored()) {
                    continue;
                }
            } catch (Throwable) {
                $checks[] = $this->check($queue->name(), static fn (): HealthDetail => HealthDetail::Unavailable);

                continue;
            }

            $checks[] = $this->check($queue->name(), fn (): HealthDetail => config('queue.connections.'.$queue->connection().'.driver') === 'sync'
                ? HealthDetail::Synchronous
                : $this->heartbeat(SystemHeartbeat::cacheKey($queue->name()), HealthDetail::StaleQueue));
        }

        return $checks;
    }

    private function heartbeat(string $key, HealthDetail $stale): HealthDetail
    {
        $timestamp = Cache::get($key);

        return is_int($timestamp) && $timestamp >= now()->timestamp - config('jalara.operations.heartbeat_seconds')
            ? HealthDetail::Recent : $stale;
    }

    /**
     * Production must not expose debug pages, must keep the session cookie on HTTPS, and must not tie passkeys to the rotatable application key.
     */
    private function productionSafeguards(): HealthDetail
    {
        $insecure = config('app.debug') === true
            || (str_starts_with((string) config('app.url'), 'https://') && config('session.secure') !== true)
            || config('fortify.passkeys.user_handle_secret') === config('app.key');

        return $insecure ? HealthDetail::Insecure : HealthDetail::Secure;
    }
}
