import { useHttp } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Ref } from 'vue';

import { read } from '@/routes/chat';
import type {
    ChatConversation,
    ChatMessage,
    ChatReadEvent,
} from '@/types/chat';

export function useChatReadState({
    selectedId,
    selected,
    conversations,
    messages,
    actorId,
    scheduleInboxRefresh,
    isDisposed,
}: {
    selectedId: Ref<string | null>;
    selected: Readonly<Ref<ChatConversation | null>>;
    conversations: Ref<ChatConversation[]>;
    messages: Ref<ChatMessage[]>;
    actorId: Readonly<Ref<string>>;
    scheduleInboxRefresh: () => void;
    isDisposed: () => boolean;
}) {
    const readError = ref(false);
    const readHttp = useHttp<
        { sequence: number },
        { last_read_sequence: number }
    >({ sequence: 0 });
    let queuedRead: { id: string; sequence: number } | null = null;

    async function persistRead(id: string, sequence: number): Promise<void> {
        readError.value = false;
        readHttp.sequence = sequence;
        try {
            const result = await readHttp.patch(read.url(id));
            if (isDisposed()) return;
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
            if (!isDisposed() && selectedId.value === id)
                readError.value = true;
        }
    }

    async function drainReadQueue(): Promise<void> {
        const next = queuedRead;
        queuedRead = null;
        if (!next || isDisposed()) return;
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
        const visibleThrough = sequence ?? messages.value.at(-1)?.sequence;
        const target = messages.value.findLast(
            (message) =>
                message.sender_id !== actorId.value &&
                message.sequence <= (visibleThrough ?? 0),
        )?.sequence;
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

    function receiveRead(event: ChatReadEvent): void {
        if (isDisposed()) return;
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
        if (event.user_id === actorId.value) scheduleInboxRefresh();
    }

    function reset(): void {
        queuedRead = null;
        readError.value = false;
    }

    function clear(): void {
        queuedRead = null;
        readHttp.cancel();
    }

    return { readError, readHttp, markRead, receiveRead, reset, clear };
}
