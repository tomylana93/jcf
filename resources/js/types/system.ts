/** Mirrors App\Enums\HealthDetail; tests/Feature/Enums/FrontendEnumMirrorTest.php keeps it in sync. */
export type HealthDetail =
    | 'reachable'
    | 'writable'
    | 'recent'
    | 'configured'
    | 'secure'
    | 'synchronous'
    | 'stale_queue'
    | 'stale_scheduler'
    | 'stale_backup'
    | 'not_configured'
    | 'disabled'
    | 'unavailable'
    | 'insecure';

export type HealthStatus = 'ok' | 'warning' | 'failed';

/** Mirrors the check arrays returned by App\Actions\System\GetSystemHealth; health checks are results, not entities, so they have no Resource. */
export type SystemHealthCheck = {
    name:
        | 'database'
        | 'storage'
        | 'cache'
        | 'queue'
        | 'scheduler'
        | 'mail'
        | 'security'
        | 'backup';
    status: HealthStatus;
    detail: HealthDetail;
};
/** `checks` is a deferred prop and stays undefined until the follow-up request resolves. */
export type SystemPageProps = { checks?: SystemHealthCheck[] };
