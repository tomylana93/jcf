export type SystemHealthCheck = {
    name:
        | 'database'
        | 'storage'
        | 'cache'
        | 'queue'
        | 'scheduler'
        | 'mail'
        | 'backup';
    status: 'ok' | 'warning' | 'failed';
    detail:
        | 'reachable'
        | 'writable'
        | 'recent'
        | 'configured'
        | 'unavailable'
        | 'synchronous'
        | 'stale'
        | 'not_configured';
};
export type SystemPageProps = { checks: SystemHealthCheck[] };
