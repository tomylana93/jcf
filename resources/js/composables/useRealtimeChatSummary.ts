import { router, usePage } from '@inertiajs/vue3';
import { onMounted, onScopeDispose, watch } from 'vue';

import { getRealtimeEcho } from '@/lib/echo';
import type { Auth } from '@/types';

export function useRealtimeChatSummary(): void {
    const page = usePage<{ auth: Auth }>();

    let dispose = () => {};

    onMounted(() => {
        const stop = watch(
            () => page.props.auth?.user?.id,
            (userId, _, onCleanup) => {
                if (!userId) {
                    return;
                }

                const echo = getRealtimeEcho();
                if (!echo) {
                    return;
                }

                const name = `App.Models.User.${userId}`;
                const channel = echo.private(name);

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
                            only: ['chatSummary'],
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

                const onMessage = () => refresh();
                const onRead = () => refresh();

                channel.listen('ChatMessageCreated', onMessage);
                channel.listen('ChatReadUpdated', onRead);
                channel.subscribed(refresh);

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
                    channel.stopListening('ChatMessageCreated', onMessage);
                    channel.stopListening('ChatReadUpdated', onRead);
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
