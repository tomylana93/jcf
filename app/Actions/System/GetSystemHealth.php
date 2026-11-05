<?php

declare(strict_types=1);

namespace App\Actions\System;

use App\Enums\HealthDetail;
use App\Enums\Mailer;
use App\Settings\MailSettings;
use App\Support\Contracts\HealthCheck;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final readonly class GetSystemHealth
{
    /** @param iterable<HealthCheck> $contributedChecks */
    public function __construct(private MailSettings $mailSettings, private iterable $contributedChecks = []) {}

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
            $this->check('queue', function (): HealthDetail {
                if (config('queue.connections.'.config('queue.default').'.driver') === 'sync') {
                    return HealthDetail::Synchronous;
                }

                return $this->heartbeat('system.queue_heartbeat');
            }),
            $this->check('scheduler', fn (): HealthDetail => $this->heartbeat('system.scheduler_heartbeat')),
            $this->check('mail', function (): HealthDetail {
                $mailer = config('mail.default');

                return ($mailer === 'settings' && $this->mailSettings->mailer === Mailer::Smtp->value && $this->mailSettings->smtp_host && $this->mailSettings->from_address)
                    || ($mailer === 'smtp' && config('mail.mailers.smtp.host') && config('mail.from.address'))
                    ? HealthDetail::Configured : HealthDetail::NotConfigured;
            }),
        ];

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

    private function heartbeat(string $key): HealthDetail
    {
        $timestamp = Cache::get($key);

        return is_int($timestamp) && $timestamp >= now()->timestamp - config('jalara.operations.heartbeat_seconds')
            ? HealthDetail::Recent : HealthDetail::Stale;
    }
}
