<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
};

withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});

const isOpen = usePage().props.sidebarOpen;
</script>

<template>
    <SidebarProvider v-if="variant === 'header'" class="flex-col">
        <slot />
    </SidebarProvider>
    <SidebarProvider v-else :default-open="isOpen">
        <slot />
    </SidebarProvider>
</template>
