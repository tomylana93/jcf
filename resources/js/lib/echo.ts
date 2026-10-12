import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let echo: Echo<'reverb'> | undefined;

export function getRealtimeEcho(): Echo<'reverb'> | undefined {
    if (typeof window === 'undefined' || !import.meta.env.VITE_REVERB_APP_KEY) {
        return;
    }

    if (!echo) {
        const options = {
            broadcaster: 'reverb' as const,
            key: String(import.meta.env.VITE_REVERB_APP_KEY),
            wsHost: String(
                import.meta.env.VITE_REVERB_HOST || window.location.hostname,
            ),
            wsPort: Number(import.meta.env.VITE_REVERB_PORT || 80),
            wssPort: Number(import.meta.env.VITE_REVERB_PORT || 443),
            forceTLS:
                (import.meta.env.VITE_REVERB_SCHEME || 'https') === 'https',
            enabledTransports: ['ws' as const, 'wss' as const],
            // NOTE: intentionally not imported from wayfinder-generated actions
            // (backend broadcast route is not generated in this repo).
            // Keep in sync with the framework default: /broadcasting/auth.
            authEndpoint: '/broadcasting/auth',
        };
        echo = new Echo({
            ...options,
            client: new Pusher(options.key, { ...options, cluster: '' }),
            withoutInterceptors: true,
        });
    }

    return echo;
}

export function disconnectRealtimeEcho(): void {
    echo?.disconnect();
    echo = undefined;
}
