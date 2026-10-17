<script setup lang="ts">
import Heading from '@/components/shared/Heading.vue';

defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex w-full min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="title"
            :description="description"
        />
        <slot />
    </div>
</template>
