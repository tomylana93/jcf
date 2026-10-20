<script setup lang="ts">
import { computed } from 'vue';

import { Badge } from '@/components/ui/badge';
import { useTranslations } from '@/composables/useTranslations';
import type { ChatRealtimeState } from '@/types/chat';

const props = defineProps<{ state: ChatRealtimeState }>();
const { t } = useTranslations();
const label = computed(() => t(`chat.status.${props.state}`));
const dot = computed(
    () =>
        ({
            connected: 'bg-emerald-500',
            connecting: 'bg-amber-500',
            disconnected: 'bg-destructive',
        })[props.state],
);
</script>
<template>
    <Badge
        :variant="state === 'disconnected' ? 'destructive' : 'outline'"
        :data-state="state"
        data-test="chat-connection"
        role="status"
        aria-live="polite"
        class="gap-1.5"
    >
        <span
            aria-hidden="true"
            :class="['size-1.5 rounded-full', dot]"
        />
        {{ label }}
    </Badge>
</template>
