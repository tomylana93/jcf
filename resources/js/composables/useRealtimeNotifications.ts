import { router, usePage } from '@inertiajs/vue3';
import { onMounted, onScopeDispose, watch } from 'vue';

import { disconnectRealtimeEcho, getRealtimeEcho } from '@/lib/echo';
import type { Auth, BroadcastNotification, Features } from '@/types';

export function useRealtimeNotifications(): void {
    const page = usePage<{ auth: Auth; features: Features }>();

    let dispose = () => {};

    onMounted(() => {
        const stop = watch(
            [
                () => page.props.auth?.user?.id,
                () => page.props.features.realtime,
            ],
            ([userId, realtimeEnabled], _, onCleanup) => {
                if (!userId || !realtimeEnabled) {
                    disconnectRealtimeEcho();
                    return;
                }

                const echo = getRealtimeEcho(realtimeEnabled);
                if (!echo) {
                    return;
                }

                const name = `App.Models.User.${userId}`;

                const seen = new Set<string>();

                let active = true;

                let timer: ReturnType<typeof setTimeout> | undefined;

                let loading = false;

                let pending = false;

                const refresh = () => {
                    if (!active) {
                        return;
                    }
                    pending = true;
                    if (timer || loading) {
                        return;
                    }
                    timer = setTimeout(() => {
                        timer = undefined;
                        pending = false;
                        loading = true;
                        router.reload({
                            only:
                                page.component === 'notifications/Index'
                                    ? ['notificationSummary', 'notifications']
                                    : ['notificationSummary'],
                            preserveErrors: true,
                            onFinish: () => {
                                loading = false;
                                if (pending) {
                                    refresh();
                                }
                            },
                        });
                    }, 100);
                };

                echo.private(name)
                    .notification((notification: BroadcastNotification) => {
                        if (seen.has(notification.id)) {
                            return;
                        }
                        seen.add(notification.id);
                        if (seen.size > 200) {
                            seen.delete(seen.values().next().value!);
                        }
                        refresh();
                    })
                    .listen('NotificationRead', refresh)
                    .subscribed(refresh);

                const resume = () => {
                    if (document.visibilityState === 'visible') {
                        refresh();
                    }
                };
                window.addEventListener('focus', resume);
                document.addEventListener('visibilitychange', resume);

                onCleanup(() => {
                    active = false;
                    clearTimeout(timer);
                    echo.leave(name);
                    disconnectRealtimeEcho();
                    window.removeEventListener('focus', resume);
                    document.removeEventListener('visibilitychange', resume);
                });
            },
            { immediate: true },
        );
        dispose = stop;
    });

    onScopeDispose(() => dispose());
}
