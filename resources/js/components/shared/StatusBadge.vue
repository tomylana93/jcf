<script setup lang="ts">
import { computed } from 'vue';

import { Badge } from '@/components/ui/badge';
import { getBadgeColorClasses } from '@/lib/badgeColors';

const props = withDefaults(
    defineProps<{
        label: string;
        tone?: 'neutral' | 'success' | 'warning' | 'danger' | 'info';
    }>(),
    { tone: 'neutral' },
);

const colors = {
    neutral: 'neutral',
    success: 'green',
    warning: 'amber',
    danger: 'red',
    info: 'blue',
} as const;

const colorClass = computed(() => getBadgeColorClasses(colors[props.tone]));
</script>

<template>
    <Badge
        variant="secondary"
        :class="colorClass"
    >
        {{ label }}
    </Badge>
</template>
