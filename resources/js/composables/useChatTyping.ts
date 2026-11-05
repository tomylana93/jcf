import { useHttp } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Ref } from 'vue';

import { typing as typingRoute } from '@/routes/chat';
import type { ChatConversation, ChatTypingEvent } from '@/types/chat';

export function useChatTyping({
    selectedId,
    selected,
    draft,
    actorId,
    isDisposed,
}: {
    selectedId: Ref<string | null>;
    selected: Readonly<Ref<ChatConversation | null>>;
    draft: Ref<string>;
    actorId: Readonly<Ref<string>>;
    isDisposed: () => boolean;
}) {
    const typingHttp = useHttp<{ typing: boolean }, { typing: boolean }>({
        typing: false,
    });
    const recipientTyping = ref(false);
    const typingActive = ref(false);
    let typingIdleTimer: ReturnType<typeof setTimeout> | undefined;
    let typingExpiryTimer: ReturnType<typeof setTimeout> | undefined;
    let typingConversationId: string | null = null;
    let typingSentAt = 0;
    let queuedTyping: { id: string; typing: boolean } | null = null;

    function clearRecipientTyping(): void {
        if (typingExpiryTimer) clearTimeout(typingExpiryTimer);
        typingExpiryTimer = undefined;
        recipientTyping.value = false;
    }

    function dispatchTyping(id: string, typing: boolean): void {
        if (isDisposed()) return;
        if (typingHttp.processing) {
            queuedTyping = { id, typing };

            return;
        }
        typingHttp.typing = typing;
        void typingHttp
            .post(typingRoute.url(id))
            .then(() => {
                if (typing) typingSentAt = Date.now();
            })
            .catch(() => {
                if (typing && typingConversationId === id)
                    typingActive.value = false;
            })
            .finally(() => {
                const next = queuedTyping;
                queuedTyping = null;
                if (next && !isDisposed()) dispatchTyping(next.id, next.typing);
            });
    }

    function stopTyping(notify = true): void {
        if (typingIdleTimer) clearTimeout(typingIdleTimer);
        typingIdleTimer = undefined;
        const id = typingConversationId;
        const wasActive = typingActive.value;
        typingActive.value = false;
        typingConversationId = null;
        if (notify && wasActive && id) dispatchTyping(id, false);
    }

    function notifyTyping(): void {
        const id = selectedId.value;
        if (!id || !selected.value?.can_send) return;
        if (!draft.value.trim()) {
            stopTyping();

            return;
        }
        if (typingIdleTimer) clearTimeout(typingIdleTimer);
        typingIdleTimer = setTimeout(stopTyping, 2200);
        if (
            typingActive.value &&
            typingConversationId === id &&
            Date.now() - typingSentAt < 2000
        ) {
            return;
        }
        typingActive.value = true;
        typingConversationId = id;
        typingSentAt = Date.now();
        dispatchTyping(id, true);
    }

    function receiveTyping(event: ChatTypingEvent): void {
        if (
            isDisposed() ||
            event.user_id === actorId.value ||
            event.conversation_id !== selectedId.value
        ) {
            return;
        }
        clearRecipientTyping();
        if (!event.typing) return;
        const expiresAt = Date.parse(event.expires_at);
        const delay = Number.isFinite(expiresAt)
            ? Math.max(0, expiresAt - Date.now())
            : 4000;
        if (delay === 0) return;
        recipientTyping.value = true;
        typingExpiryTimer = setTimeout(
            () => {
                typingExpiryTimer = undefined;
                recipientTyping.value = false;
            },
            Math.min(delay, 5000),
        );
    }

    function clear(): void {
        stopTyping(false);
        clearRecipientTyping();
        queuedTyping = null;
        typingHttp.cancel();
    }

    return {
        recipientTyping,
        clearRecipientTyping,
        stopTyping,
        notifyTyping,
        receiveTyping,
        clear,
    };
}
