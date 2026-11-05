/** Mirrors App\Enums\HealthDetail; tests/Feature/Enums/FrontendEnumMirrorTest.php keeps it in sync. */
export type HealthDetail =
    | 'reachable'
    | 'writable'
    | 'recent'
    | 'configured'
    | 'synchronous'
    | 'stale'
    | 'not_configured'
    | 'disabled'
    | 'unavailable';

export type HealthStatus = 'ok' | 'warning' | 'failed';

export type SystemHealthCheck = {
    name:
        | 'database'
        | 'storage'
        | 'cache'
        | 'queue'
        | 'scheduler'
        | 'mail'
        | 'backup';
    status: HealthStatus;
    detail: HealthDetail;
};
export type SystemPageProps = { checks: SystemHealthCheck[] };
