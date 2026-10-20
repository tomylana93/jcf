<script setup lang="ts">
import { computed, onMounted, onScopeDispose, watch } from 'vue';

import { useMessageScrollerVisibility } from '@/components/ui/message-scroller';
import type { ChatMessage } from '@/types/chat';

const props = defineProps<{ messages: ChatMessage[] }>();
const emit = defineEmits<{ read: [sequence: number] }>();

const visibility = useMessageScrollerVisibility();

const sequences = computed(
    () =>
        new Map(
            props.messages.map((message) => [message.id, message.sequence]),
        ),
);

const highestVisibleSequence = computed(() =>
    visibility.value.visibleMessageIds.reduce(
        (highest, id) => Math.max(highest, sequences.value.get(id) ?? 0),
        0,
    ),
);

function announce(): void {
    if (
        typeof document === 'undefined' ||
        !document.hasFocus() ||
        document.visibilityState !== 'visible'
    ) {
        return;
    }

    if (highestVisibleSequence.value > 0) {
        emit('read', highestVisibleSequence.value);
    }
}

watch(highestVisibleSequence, announce);

onMounted(() => {
    window.addEventListener('focus', announce);
    document.addEventListener('visibilitychange', announce);
    announce();
});

onScopeDispose(() => {
    window.removeEventListener('focus', announce);
    document.removeEventListener('visibilitychange', announce);
});
</script>

<template>
    <span
        hidden
        aria-hidden="true"
    />
</template>
