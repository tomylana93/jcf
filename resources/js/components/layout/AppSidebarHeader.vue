<script setup lang="ts">
import Breadcrumbs from '@/components/layout/Breadcrumbs.vue';
import AppearanceSwitch from '@/components/shared/AppearanceSwitch.vue';
import GlobalSearchTrigger from '@/components/shared/GlobalSearchTrigger.vue';
import LocaleSelector from '@/components/shared/LocaleSelector.vue';
import NotificationBell from '@/components/shared/NotificationBell.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex min-w-0 flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <GlobalSearchTrigger />
        <LocaleSelector class="shrink-0" />
        <NotificationBell />
        <AppearanceSwitch class="shrink-0" />
    </header>
</template>
