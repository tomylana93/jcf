import { useHttp } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Ref } from 'vue';

import { history } from '@/routes/chat';
import type { ChatHistory, ChatMessage } from '@/types/chat';

export function useChatHistory({
    selectedId,
    confirm,
    isDisposed,
}: {
    selectedId: Ref<string | null>;
    confirm: (id: string, messages: ChatMessage[]) => void;
    isDisposed: () => boolean;
}) {
    const messages = ref<ChatMessage[]>([]);
    const hasOlder = ref(false);
    const loadedThrough = ref(0);
    const historyError = ref(false);
    const historyHttp = useHttp<Record<string, never>, ChatHistory>({});
    let generation = 0;
    let historyQueued = false;

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
                if (isDisposed() || generation !== version) return;
                messages.value = mergeMessages(messages.value, result.data);
                confirm(id, result.data);
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
            if (!isDisposed() && generation === version)
                historyError.value = true;
        } finally {
            if (!isDisposed() && generation === version && historyQueued) {
                historyQueued = false;
                void loadHistory();
            }
        }
    }

    function reset(): void {
        generation++;
        historyQueued = false;
        historyHttp.cancel();
        messages.value = [];
        hasOlder.value = false;
        loadedThrough.value = 0;
    }

    function merge(incoming: ChatMessage[]): void {
        messages.value = mergeMessages(messages.value, incoming);
    }

    return {
        messages,
        hasOlder,
        loadedThrough,
        historyError,
        historyHttp,
        loadHistory,
        merge,
        reset,
    };
}
