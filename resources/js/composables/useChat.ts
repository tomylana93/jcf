import { router, useHttp, usePage } from '@inertiajs/vue3';
import { computed, onScopeDispose, ref, watch } from 'vue';

import { logout } from '@/routes';
import { history, inbox, read, send, store } from '@/routes/chat';
import type { Auth } from '@/types';
import type {
    ChatConversation,
    ChatHistory,
    ChatMessage,
    ChatPage,
    PendingChatMessage,
} from '@/types/chat';

export function useChat() {
    const page = usePage<{ auth: Auth }>();
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
        ChatPage<ChatConversation>
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
    let generation = 0;
    let disposed = false;

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
        if (inboxHttp.processing) return;
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
        }
    }

    async function loadHistory(older = false): Promise<void> {
        const id = selectedId.value;
        if (!id || (older && historyHttp.processing)) return;
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
                if (!older) loadedThrough.value = after ?? 0;
            } while (more);
        } catch {
            if (!disposed && generation === version) historyError.value = true;
        }
    }

    function select(conversation: ChatConversation): void {
        generation++;
        historyHttp.cancel();
        selectedId.value = conversation.id;
        messages.value = [];
        hasOlder.value = false;
        loadedThrough.value = 0;
        readError.value = false;
        void loadHistory();
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

    async function markRead(): Promise<void> {
        const conversation = selected.value;
        const sequence = messages.value.at(-1)?.sequence;
        if (!conversation || !sequence || readHttp.processing) return;
        readError.value = false;
        readHttp.sequence = sequence;
        try {
            const result = await readHttp.patch(read.url(conversation.id));
            if (disposed) return;
            conversation.last_read_sequence = result.last_read_sequence;
            // Reload canonical unread counts; messages sent concurrently may remain unread.
            await loadInbox();
        } catch {
            if (!disposed) readError.value = true;
        }
    }

    function clear(): void {
        disposed = true;
        generation++;
        for (const http of [
            inboxHttp,
            historyHttp,
            sendHttp,
            startHttp,
            readHttp,
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
        inboxHttp,
        historyHttp,
        sendHttp,
        startHttp,
        readHttp,
        loadInbox,
        loadHistory,
        select,
        start,
        submit,
        transmit,
        markRead,
    };
}
