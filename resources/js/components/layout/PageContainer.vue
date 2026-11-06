<script setup lang="ts">
import Heading from '@/components/shared/Heading.vue';

defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex w-full min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
        <div
            v-if="$slots.actions"
            class="flex flex-wrap items-start justify-between gap-x-4"
        >
            <Heading
                :level="1"
                :title="title"
                :description="description"
            />
            <div class="flex flex-wrap items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
        <Heading
            v-else
            :level="1"
            :title="title"
            :description="description"
        />
        <slot />
    </div>
</template>
