<script setup lang="ts">
import { computed } from 'vue';

import { Badge } from '@/components/ui/badge';
import { getBadgeColorClasses } from '@/lib/badgeColors';

type Props = {
    color?: string;
    label: string;
};

const props = withDefaults(defineProps<Props>(), {
    color: 'neutral',
});

const customClass = computed(() => getBadgeColorClasses(props.color));
</script>

<template>
    <Badge
        variant="secondary"
        :class="customClass"
    >
        {{ label }}
    </Badge>
</template>
