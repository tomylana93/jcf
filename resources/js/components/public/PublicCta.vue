<script setup lang="ts">
import PublicLink from './PublicLink.vue';

defineProps<{ label: string; href: string }>();
</script>

<template>
    <PublicLink
        data-slot="button"
        :href="href"
        class="inline-flex min-h-11 max-w-full items-center justify-center rounded-md bg-primary px-5 py-3 text-center text-sm font-semibold wrap-anywhere text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring motion-reduce:transition-none"
    >
        {{ label }}
    </PublicLink>
</template>
