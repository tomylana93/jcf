<script setup lang="ts">
/** Only sanitized HTML from the backend publishing boundary is accepted. */
defineProps<{ html: string }>();
</script>

<template>
    <div
        class="editor-content max-w-[70ch] min-w-0 wrap-anywhere [&_img]:h-auto [&_img]:max-w-full"
        v-html="html"
    />
</template>
