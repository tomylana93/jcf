import { useHttp } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { Ref } from 'vue';

import { send } from '@/routes/chat';
import type {
    ChatConversation,
    ChatMessage,
    PendingChatMessage,
} from '@/types/chat';

export function useChatDelivery({
    selectedId,
    selected,
    conversations,
    onMessage,
    stopTyping,
    isDisposed,
}: {
    selectedId: Ref<string | null>;
    selected: Readonly<Ref<ChatConversation | null>>;
    conversations: Ref<ChatConversation[]>;
    onMessage: (message: ChatMessage) => void;
    stopTyping: () => void;
    isDisposed: () => boolean;
}) {
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
    const sendHttp = useHttp<
        { body: string; client_message_id: string },
        { data: ChatMessage }
    >({ body: '', client_message_id: '' });

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
            if (isDisposed()) return;
            pending.value[id] = (pending.value[id] ?? []).filter(
                (item) => item.client_message_id !== message.client_message_id,
            );
            if (selectedId.value === id) onMessage(result.data);
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
            if (!isDisposed()) {
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

    function confirm(id: string, messages: ChatMessage[]): void {
        const confirmed = new Set(
            messages.map((message) => message.client_message_id),
        );
        pending.value[id] = (pending.value[id] ?? []).filter(
            (message) => !confirmed.has(message.client_message_id),
        );
    }

    function clear(): void {
        sendHttp.cancel();
        drafts.value = {};
        pending.value = {};
    }

    return { draft, outgoing, sendHttp, submit, transmit, confirm, clear };
}
