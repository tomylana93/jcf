import { usePage } from '@inertiajs/vue3';
import { onMounted, onScopeDispose, ref, watch } from 'vue';

import { getRealtimeEcho } from '@/lib/echo';
import type { Auth } from '@/types';
import type {
    ChatMessage,
    ChatReadEvent,
    ChatRealtimeState,
    ChatTypingEvent,
} from '@/types/chat';

type ChatRealtimeHandlers = {
    onMessage: (message: ChatMessage) => void;
    onRead: (event: ChatReadEvent) => void;
    onTyping: (event: ChatTypingEvent) => void;
    onCatchUp: () => void;
};

export function useChatRealtime(handlers: ChatRealtimeHandlers) {
    const page = usePage<{ auth: Auth }>();
    const state = ref<ChatRealtimeState>('connecting');

    let dispose = () => {};

    onMounted(() => {
        const stop = watch(
            () => page.props.auth?.user?.id,
            (userId, _, onCleanup) => {
                state.value = 'connecting';

                if (!userId) {
                    state.value = 'disconnected';

                    return;
                }

                const echo = getRealtimeEcho();

                if (!echo) {
                    state.value = 'disconnected';

                    return;
                }

                let active = true;
                const name = `App.Models.User.${userId}`;
                const channel = echo.private(name);
                const onMessage = (message: ChatMessage) => {
                    if (active) {
                        handlers.onMessage(message);
                    }
                };
                const onRead = (event: ChatReadEvent) => {
                    if (active) {
                        handlers.onRead(event);
                    }
                };
                const onTyping = (event: ChatTypingEvent) => {
                    if (active) {
                        handlers.onTyping(event);
                    }
                };
                const catchUp = () => {
                    if (active) {
                        handlers.onCatchUp();
                    }
                };

                channel.listen('ChatMessageCreated', onMessage);
                channel.listen('ChatReadUpdated', onRead);
                channel.listen('ChatTypingUpdated', onTyping);

                const status = echo.connectionStatus();
                state.value =
                    status === 'connected'
                        ? 'connected'
                        : status === 'connecting'
                          ? 'connecting'
                          : 'disconnected';

                const stopConnection = echo.connector.onConnectionChange(
                    (status) => {
                        if (status === 'connected') {
                            const wasConnected = state.value === 'connected';
                            state.value = 'connected';
                            if (!wasConnected) {
                                catchUp();
                            }
                        } else {
                            state.value =
                                status === 'connecting'
                                    ? 'connecting'
                                    : 'disconnected';
                        }
                    },
                );

                channel.subscribed(() => {
                    if (!active) {
                        return;
                    }

                    state.value = 'connected';
                    catchUp();
                });

                const resume = () => {
                    if (
                        document.visibilityState === 'visible' &&
                        navigator.onLine
                    ) {
                        catchUp();
                    }
                };
                const offline = () => {
                    if (active) {
                        state.value = 'disconnected';
                    }
                };
                const online = () => {
                    if (!active) {
                        return;
                    }

                    state.value =
                        echo.connectionStatus() === 'connected'
                            ? 'connected'
                            : 'connecting';
                    catchUp();
                };

                document.addEventListener('visibilitychange', resume);
                window.addEventListener('offline', offline);
                window.addEventListener('online', online);

                onCleanup(() => {
                    active = false;
                    channel.stopListening('ChatMessageCreated', onMessage);
                    channel.stopListening('ChatReadUpdated', onRead);
                    channel.stopListening('ChatTypingUpdated', onTyping);
                    stopConnection();
                    document.removeEventListener('visibilitychange', resume);
                    window.removeEventListener('offline', offline);
                    window.removeEventListener('online', online);
                });
            },
            { immediate: true },
        );
        dispose = stop;
    });

    onScopeDispose(() => dispose());

    return { state };
}
