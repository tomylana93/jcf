import { router, useHttp, usePage } from '@inertiajs/vue3';
import { computed, onScopeDispose, ref, watch } from 'vue';

import { useChatRealtime } from '@/composables/useChatRealtime';
import { logout } from '@/routes';
import {
    history,
    inbox,
    read,
    send,
    store,
    typing as typingRoute,
} from '@/routes/chat';
import type { Auth } from '@/types';
import type {
    ChatConversation,
    ChatHistory,
    ChatMessage,
    ChatPage,
    ChatReadEvent,
    ChatSummary,
    ChatTypingEvent,
    PendingChatMessage,
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
    const messages = ref<ChatMessage[]>([]);
    const drafts = ref<Record<string, string>>({});
    const pending = ref<Record<string, PendingChatMessage[]>>({});
    const draft = computed({
        get: () => drafts.value[selectedId.value ?? ''] ?? '',
        set: (value) => {
            if (selectedId.value) drafts.value[selectedId.value] = value;
        },
    });
    const outgoing = computed(
        () => pending.value[selectedId.value ?? ''] ?? [],
    );
    const inboxCursor = ref<string | null>(null);
    const hasOlder = ref(false);
    const loadedThrough = ref(0);
    const inboxError = ref(false);
    const historyError = ref(false);
    const startError = ref(false);
    const readError = ref(false);
    const inboxHttp = useHttp<
        Record<string, never>,
        ChatPage<ChatConversation> & { summary: ChatSummary }
    >({});
    const historyHttp = useHttp<Record<string, never>, ChatHistory>({});
    const sendHttp = useHttp<
        { body: string; client_message_id: string },
        { data: ChatMessage }
    >({ body: '', client_message_id: '' });
    const startHttp = useHttp<
        { recipient_id: string },
        { data: ChatConversation }
    >({ recipient_id: '' });
    const readHttp = useHttp<
        { sequence: number },
        { last_read_sequence: number }
    >({ sequence: 0 });
    const typingHttp = useHttp<{ typing: boolean }, { typing: boolean }>({
        typing: false,
    });
    const recipientTyping = ref(false);
    const typingActive = ref(false);
    let generation = 0;
    let disposed = false;
    let inboxQueued = false;
    let historyQueued = false;
    let inboxTimer: ReturnType<typeof setTimeout> | undefined;
    let historyTimer: ReturnType<typeof setTimeout> | undefined;
    let typingIdleTimer: ReturnType<typeof setTimeout> | undefined;
    let typingExpiryTimer: ReturnType<typeof setTimeout> | undefined;
    let typingConversationId: string | null = null;
    let typingSentAt = 0;
    let queuedRead: { id: string; sequence: number } | null = null;
    let queuedTyping: { id: string; typing: boolean } | null = null;
    const actorId = computed(() => page.props.auth?.user?.id ?? '');

    function mergeMessages(
        current: ChatMessage[],
        incoming: ChatMessage[],
    ): ChatMessage[] {
        const records = new Map(
            current.map((message) => [message.id, message]),
        );
        for (const message of incoming) records.set(message.id, message);
        return [...records.values()].sort((a, b) => a.sequence - b.sequence);
    }

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

    async function loadHistory(older = false): Promise<void> {
        const id = selectedId.value;
        if (!id) return;
        if (historyHttp.processing) {
            if (!older) historyQueued = true;

            return;
        }
        const version = generation;
        historyError.value = false;
        try {
            const first = messages.value[0]?.sequence;
            let after =
                !older && loadedThrough.value ? loadedThrough.value : undefined;
            let more: boolean;
            do {
                const result = await historyHttp.get(
                    history.url(id, {
                        query:
                            older && first
                                ? { before: first }
                                : after
                                  ? { after }
                                  : {},
                    }),
                );
                if (disposed || generation !== version) return;
                messages.value = mergeMessages(messages.value, result.data);
                const confirmed = new Set(
                    result.data.map((message) => message.client_message_id),
                );
                pending.value[id] = (pending.value[id] ?? []).filter(
                    (message) => !confirmed.has(message.client_message_id),
                );
                if (!after) hasOlder.value = result.has_more;
                more = Boolean(after && result.has_more);
                after = result.data.at(-1)?.sequence ?? after;
                if (!older)
                    loadedThrough.value = Math.max(
                        loadedThrough.value,
                        after ?? 0,
                    );
            } while (more);
        } catch {
            if (!disposed && generation === version) historyError.value = true;
        } finally {
            if (!disposed && generation === version && historyQueued) {
                historyQueued = false;
                void loadHistory();
            }
        }
    }

    function clearRecipientTyping(): void {
        if (typingExpiryTimer) clearTimeout(typingExpiryTimer);
        typingExpiryTimer = undefined;
        recipientTyping.value = false;
    }

    function dispatchTyping(id: string, typing: boolean): void {
        if (disposed) return;
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
                if (next && !disposed) dispatchTyping(next.id, next.typing);
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

    function select(conversation: ChatConversation): void {
        generation++;
        historyQueued = false;
        historyHttp.cancel();
        queuedRead = null;
        stopTyping();
        clearRecipientTyping();
        selectedId.value = conversation.id;
        messages.value = [];
        hasOlder.value = false;
        loadedThrough.value = 0;
        readError.value = false;
        void loadHistory();
    }

    function close(): void {
        stopTyping();
        clearRecipientTyping();
        queuedRead = null;
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

    async function transmit(
        id: string,
        message: PendingChatMessage,
    ): Promise<void> {
        if (sendHttp.processing) return;
        message.status = 'sending';
        sendHttp.body = message.body;
        sendHttp.client_message_id = message.client_message_id;
        try {
            const result = await sendHttp.post(send.url(id));
            if (disposed) return;
            pending.value[id] = (pending.value[id] ?? []).filter(
                (item) => item.client_message_id !== message.client_message_id,
            );
            if (selectedId.value === id)
                messages.value = mergeMessages(messages.value, [result.data]);
            const conversation = conversations.value.find(
                (item) => item.id === id,
            );
            if (
                conversation &&
                result.data.sequence >= conversation.last_message_sequence
            ) {
                conversation.last_message = result.data;
                conversation.last_message_sequence = result.data.sequence;
                conversations.value = [
                    conversation,
                    ...conversations.value.filter((item) => item.id !== id),
                ];
            }
        } catch {
            if (!disposed) {
                const entry = pending.value[id]?.find(
                    (item) =>
                        item.client_message_id === message.client_message_id,
                );
                if (entry) entry.status = 'failed';
            }
        }
    }

    function submit(): void {
        const id = selectedId.value;
        const body = draft.value.trim();
        if (
            !id ||
            !body ||
            Array.from(body).length > 4000 ||
            sendHttp.processing ||
            !selected.value?.can_send
        )
            return;
        stopTyping();
        const message: PendingChatMessage = {
            body,
            client_message_id: crypto.randomUUID(),
            created_at: new Date().toISOString(),
            status: 'sending',
        };
        pending.value[id] = [...(pending.value[id] ?? []), message];
        draft.value = '';
        void transmit(id, message);
    }

    async function persistRead(id: string, sequence: number): Promise<void> {
        readError.value = false;
        readHttp.sequence = sequence;
        try {
            const result = await readHttp.patch(read.url(id));
            if (disposed) return;
            const conversation = conversations.value.find(
                (item) => item.id === id,
            );
            if (conversation) {
                conversation.last_read_sequence = Math.max(
                    conversation.last_read_sequence,
                    result.last_read_sequence,
                );
            }
            // Reload canonical unread counts; messages sent concurrently may remain unread.
            scheduleInboxRefresh();
        } catch {
            if (!disposed && selectedId.value === id) readError.value = true;
        }
    }

    async function drainReadQueue(): Promise<void> {
        const next = queuedRead;
        queuedRead = null;
        if (!next || disposed) return;
        const conversation = conversations.value.find(
            (item) => item.id === next.id,
        );
        if (!conversation || next.sequence <= conversation.last_read_sequence)
            return;
        await persistRead(next.id, next.sequence);
        await drainReadQueue();
    }

    async function markRead(sequence?: number): Promise<void> {
        const conversation = selected.value;
        const target = sequence ?? messages.value.at(-1)?.sequence;
        if (
            !conversation ||
            !target ||
            target <= conversation.last_read_sequence
        )
            return;
        const id = conversation.id;
        if (readHttp.processing) {
            queuedRead =
                queuedRead?.id === id
                    ? {
                          id,
                          sequence: Math.max(queuedRead.sequence, target),
                      }
                    : { id, sequence: target };

            return;
        }
        await persistRead(id, target);
        await drainReadQueue();
    }

    function scheduleInboxRefresh(): void {
        if (disposed || inboxTimer) return;
        inboxTimer = setTimeout(() => {
            inboxTimer = undefined;
            void loadInbox();
        }, 150);
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
        const queued = pending.value[id];
        if (
            queued?.some(
                (item) => item.client_message_id === message.client_message_id,
            )
        ) {
            pending.value[id] = queued.filter(
                (item) => item.client_message_id !== message.client_message_id,
            );
        }
        const conversation = conversations.value.find((item) => item.id === id);
        if (!conversation) {
            scheduleInboxRefresh();

            return;
        }
        if (selectedId.value === id) {
            if (message.sender_id !== actorId.value) clearRecipientTyping();
            const previous = loadedThrough.value;
            messages.value = mergeMessages(messages.value, [message]);
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

    function receiveRead(event: ChatReadEvent): void {
        if (disposed) return;
        const conversation = conversations.value.find(
            (item) => item.id === event.conversation_id,
        );
        if (conversation) {
            if (
                event.user_id === actorId.value &&
                event.last_read_sequence > conversation.last_read_sequence
            ) {
                conversation.last_read_sequence = event.last_read_sequence;
            }
            if (
                event.user_id === conversation.recipient.id &&
                event.last_read_sequence >
                    conversation.recipient_last_read_sequence
            ) {
                conversation.recipient_last_read_sequence =
                    event.last_read_sequence;
            }
        }
        scheduleInboxRefresh();
    }

    function receiveTyping(event: ChatTypingEvent): void {
        if (
            disposed ||
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
        generation++;
        clearTimeout(inboxTimer);
        clearTimeout(historyTimer);
        clearTimeout(typingIdleTimer);
        clearTimeout(typingExpiryTimer);
        inboxTimer = undefined;
        historyTimer = undefined;
        typingIdleTimer = undefined;
        typingExpiryTimer = undefined;
        inboxQueued = false;
        historyQueued = false;
        queuedRead = null;
        queuedTyping = null;
        typingActive.value = false;
        typingConversationId = null;
        recipientTyping.value = false;
        for (const http of [
            inboxHttp,
            historyHttp,
            sendHttp,
            startHttp,
            readHttp,
            typingHttp,
        ])
            http.cancel();
        conversations.value = [];
        selectedId.value = null;
        messages.value = [];
        drafts.value = {};
        pending.value = {};
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
