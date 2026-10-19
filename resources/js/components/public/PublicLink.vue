<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ inheritAttrs: false });
const props = defineProps<{ href: string }>();
const internal = computed(
    () => props.href.startsWith('/') && !props.href.startsWith('//'),
);
</script>

<template>
    <Link
        v-if="internal"
        :href="href"
        v-bind="$attrs"
    >
        <slot />
    </Link>
    <a
        v-else
        :href="href"
        v-bind="$attrs"
    >
        <slot />
    </a>
</template>
