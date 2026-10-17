<script setup lang="ts">
withDefaults(defineProps<{ width?: string }>(), { width: 'max-w-6xl' });
</script>

<template>
    <div :class="['mx-auto w-full min-w-0 px-4 sm:px-6 lg:px-8', width]">
        <slot />
    </div>
</template>
