<script setup lang="ts">
import AppContent from '@/components/layout/AppContent.vue';
import AppHeader from '@/components/layout/AppHeader.vue';
import AppShell from '@/components/layout/AppShell.vue';
import AppSidebar from '@/components/layout/AppSidebar.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="header">
        <AppSidebar mobile-only />
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent variant="header">
            <slot />
        </AppContent>
    </AppShell>
</template>
