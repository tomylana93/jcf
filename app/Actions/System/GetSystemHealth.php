<?php

declare(strict_types=1);

namespace App\Actions\System;

use App\Settings\MailSettings;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final readonly class GetSystemHealth
{
    public function __construct(private MailSettings $mailSettings) {}

    /** @return list<array{name: string, status: string, detail: string}> */
    public function handle(): array
    {
        return [
            $this->check('database', function (): string {
                DB::connection()->getPdo();
                DB::select('SELECT 1');

                return 'reachable';
            }),
            $this->check('storage', function (): string {
                $disk = Storage::disk();
                $path = '.health/'.Str::uuid();
                try {
                    if (! $disk->put($path, 'health') || $disk->get($path) !== 'health') {
                        return 'unavailable';
                    }

                    return 'writable';
                } finally {
                    $disk->delete($path);
                }
            }),
            $this->check('cache', function (): string {
                $key = 'system.probe.'.Str::uuid();
                try {
                    Cache::put($key, 'health', 30);

                    return Cache::get($key) === 'health' ? 'reachable' : 'unavailable';
                } finally {
                    Cache::forget($key);
                }
            }),
            $this->check('queue', function (): string {
                if (config('queue.connections.'.config('queue.default').'.driver') === 'sync') {
                    return 'synchronous';
                }

                return $this->heartbeat('system.queue_heartbeat');
            }),
            $this->check('scheduler', fn (): string => $this->heartbeat('system.scheduler_heartbeat')),
            $this->check('backup', function (): string {
                if (! config('jalara.operations.backup_enabled') || ! config('backup.backup.password')) {
                    return 'not_configured';
                }
                $disk = Storage::disk('backups');
                $latest = collect($disk->files(config('backup.backup.name')))->filter(fn (string $path): bool => str_ends_with($path, '.zip'))->map(fn (string $path): int => $disk->lastModified($path))->max();

                return is_int($latest) && $latest >= now()->subDays(2)->timestamp ? 'recent' : 'stale';
            }),
            $this->check('mail', function (): string {
                $mailer = config('mail.default');

                return ($mailer === 'settings' && $this->mailSettings->mailer === 'smtp' && $this->mailSettings->smtp_host && $this->mailSettings->from_address)
                    || ($mailer === 'smtp' && config('mail.mailers.smtp.host') && config('mail.from.address'))
                    ? 'configured' : 'not_configured';
            }),
        ];
    }

    /** @return array{name: string, status: string, detail: string} */
    private function check(string $name, Closure $probe): array
    {
        try {
            $detail = $probe();
            $status = $detail === 'unavailable' ? 'failed' : (in_array($detail, ['reachable', 'writable', 'recent', 'configured'], true) ? 'ok' : 'warning');
        } catch (Throwable) {
            $detail = 'unavailable';
            $status = 'failed';
        }

        return ['name' => $name, 'status' => $status, 'detail' => $detail];
    }

    private function heartbeat(string $key): string
    {
        $timestamp = Cache::get($key);

        return is_int($timestamp) && $timestamp >= now()->timestamp - config('jalara.operations.heartbeat_seconds')
            ? 'recent' : 'stale';
    }
}
