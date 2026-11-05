import { router, useHttp, usePage } from '@inertiajs/vue3';
import { computed, onScopeDispose, ref, watch } from 'vue';

import { useChatDelivery } from '@/composables/useChatDelivery';
import { useChatHistory } from '@/composables/useChatHistory';
import { useChatReadState } from '@/composables/useChatReadState';
import { useChatRealtime } from '@/composables/useChatRealtime';
import { useChatTyping } from '@/composables/useChatTyping';
import { logout } from '@/routes';
import { inbox, store } from '@/routes/chat';
import type { Auth } from '@/types';
import type {
    ChatConversation,
    ChatMessage,
    ChatPage,
    ChatSummary,
} from '@/types/chat';

export function useChat() {
    const page = usePage<{ auth: Auth; chatSummary?: ChatSummary }>();
    const conversations = ref<ChatConversation[]>([]);
    const selectedId = ref<string | null>(null);
    const selected = computed(
        () =>
            conversations.value.find((item) => item.id === selectedId.value) ??
            null,
    );
    const inboxCursor = ref<string | null>(null);
    const inboxError = ref(false);
    const startError = ref(false);
    const inboxHttp = useHttp<
        Record<string, never>,
        ChatPage<ChatConversation> & { summary: ChatSummary }
    >({});
    const startHttp = useHttp<
        { recipient_id: string },
        { data: ChatConversation }
    >({ recipient_id: '' });
    let disposed = false;
    let inboxQueued = false;
    let inboxTimer: ReturnType<typeof setTimeout> | undefined;
    let historyTimer: ReturnType<typeof setTimeout> | undefined;
    const actorId = computed(() => page.props.auth?.user?.id ?? '');

    const isDisposed = () => disposed;
    const delivery = useChatDelivery({
        selectedId,
        selected,
        conversations,
        isDisposed,
        onMessage: (message) => chatHistory.merge([message]),
        stopTyping: () => typing.stopTyping(),
    });
    const { draft, outgoing, sendHttp, submit, transmit } = delivery;
    const chatHistory = useChatHistory({
        selectedId,
        confirm: delivery.confirm,
        isDisposed,
    });
    const {
        messages,
        hasOlder,
        loadedThrough,
        historyError,
        historyHttp,
        loadHistory,
    } = chatHistory;
    const typing = useChatTyping({
        selectedId,
        selected,
        draft,
        actorId,
        isDisposed,
    });
    const {
        recipientTyping,
        clearRecipientTyping,
        stopTyping,
        notifyTyping,
        receiveTyping,
    } = typing;
    const reads = useChatReadState({
        selectedId,
        selected,
        conversations,
        messages,
        actorId,
        scheduleInboxRefresh,
        isDisposed,
    });
    const { readError, readHttp, markRead, receiveRead } = reads;

    async function loadInbox(more = false): Promise<void> {
        if (inboxHttp.processing) {
            inboxQueued = true;

            return;
        }
        inboxError.value = false;
        try {
            const result = await inboxHttp.get(
                inbox.url({
                    query:
                        more && inboxCursor.value
                            ? { cursor: inboxCursor.value }
                            : {},
                }),
            );
            if (disposed) return;
            if (
                page.props.chatSummary?.unread_count !==
                result.summary.unread_count
            ) {
                router.replaceProp('chatSummary', result.summary);
            }
            const entries = more ? conversations.value : [];
            const records = new Map(entries.map((item) => [item.id, item]));
            for (const item of result.data) records.set(item.id, item);
            // Keep an open conversation when it moves off the first inbox page.
            if (!more && selected.value && !records.has(selected.value.id))
                records.set(selected.value.id, selected.value);
            conversations.value = [...records.values()];
            inboxCursor.value = result.next_cursor;
        } catch {
            if (!disposed) inboxError.value = true;
        } finally {
            if (!disposed && inboxQueued) {
                inboxQueued = false;
                void loadInbox();
            }
        }
    }

    function select(conversation: ChatConversation): void {
        chatHistory.reset();
        reads.reset();
        stopTyping();
        clearRecipientTyping();
        selectedId.value = conversation.id;
        void loadHistory();
    }

    function close(): void {
        stopTyping();
        clearRecipientTyping();
        reads.reset();
        selectedId.value = null;
    }

    async function start(recipientId: string): Promise<boolean> {
        if (startHttp.processing) return false;
        startHttp.recipient_id = recipientId;
        startError.value = false;
        try {
            const result = await startHttp.post(store.url());
            if (disposed) return false;
            conversations.value = [
                result.data,
                ...conversations.value.filter(
                    (item) => item.id !== result.data.id,
                ),
            ];
            select(result.data);
            return true;
        } catch {
            if (!disposed) startError.value = true;
            return false;
        }
    }

    function scheduleInboxRefresh(): void {
        if (disposed || inboxTimer) return;
        inboxTimer = setTimeout(() => {
            inboxTimer = undefined;
            void loadInbox();
        }, 1000);
    }

    function scheduleHistoryCatchUp(): void {
        if (disposed || historyTimer) return;
        historyTimer = setTimeout(() => {
            historyTimer = undefined;
            void loadHistory();
        }, 150);
    }

    function receiveMessage(message: ChatMessage): void {
        if (disposed) return;
        const id = message.conversation_id;
        delivery.confirm(id, [message]);
        const conversation = conversations.value.find((item) => item.id === id);
        if (!conversation) {
            scheduleInboxRefresh();

            return;
        }
        if (selectedId.value === id) {
            if (message.sender_id !== actorId.value) clearRecipientTyping();
            const previous = loadedThrough.value;
            chatHistory.merge([message]);
            // A gap means a delivery was missed; keep the old cursor until
            // catch-up fills the missing sequences.
            if (previous > 0 && message.sequence > previous + 1) {
                scheduleHistoryCatchUp();
            } else {
                loadedThrough.value = Math.max(
                    loadedThrough.value,
                    message.sequence,
                );
            }
        }
        if (message.sequence >= conversation.last_message_sequence) {
            conversation.last_message = message;
            conversation.last_message_sequence = message.sequence;
            conversations.value = [
                conversation,
                ...conversations.value.filter((item) => item.id !== id),
            ];
        }
        scheduleInboxRefresh();
    }

    function reconcile(): void {
        if (disposed) return;
        void loadInbox();
        if (selectedId.value) void loadHistory();
    }

    const { state: realtimeState } = useChatRealtime({
        onMessage: receiveMessage,
        onRead: receiveRead,
        onTyping: receiveTyping,
        onCatchUp: reconcile,
    });

    watch(realtimeState, (state) => {
        if (state !== 'connected') {
            stopTyping(false);
            clearRecipientTyping();
        }
    });

    function clear(): void {
        disposed = true;
        clearTimeout(inboxTimer);
        clearTimeout(historyTimer);
        inboxTimer = undefined;
        historyTimer = undefined;
        inboxQueued = false;
        chatHistory.reset();
        delivery.clear();
        typing.clear();
        reads.clear();
        inboxHttp.cancel();
        startHttp.cancel();
        conversations.value = [];
        selectedId.value = null;
    }
    const removeLogout = router.on('before', (event) => {
        if (event.detail.visit.url.pathname === logout.url()) clear();
    });
    watch(() => page.props.auth.user.id, clear);
    onScopeDispose(() => {
        removeLogout();
        clear();
    });

    return {
        conversations,
        selectedId,
        selected,
        messages,
        draft,
        outgoing,
        inboxCursor,
        hasOlder,
        inboxError,
        historyError,
        startError,
        readError,
        recipientTyping,
        realtimeState,
        inboxHttp,
        historyHttp,
        sendHttp,
        startHttp,
        readHttp,
        loadInbox,
        loadHistory,
        select,
        close,
        start,
        submit,
        transmit,
        markRead,
        notifyTyping,
        stopTyping,
    };
}
