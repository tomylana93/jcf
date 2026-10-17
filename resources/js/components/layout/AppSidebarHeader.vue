<script setup lang="ts">
import Breadcrumbs from '@/components/layout/Breadcrumbs.vue';
import AppearanceSwitch from '@/components/shared/AppearanceSwitch.vue';
import GlobalSearchTrigger from '@/components/shared/GlobalSearchTrigger.vue';
import HeaderPreferences from '@/components/shared/HeaderPreferences.vue';
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
        class="flex h-16 min-w-0 shrink-0 items-center gap-1 border-b border-sidebar-border/70 px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 sm:gap-2"
    >
        <div class="flex min-w-0 flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1 shrink-0" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
            <GlobalSearchTrigger />
            <div class="hidden sm:block"><LocaleSelector /></div>
            <NotificationBell />
            <div class="hidden sm:block"><AppearanceSwitch /></div>
            <div class="sm:hidden"><HeaderPreferences /></div>
        </div>
    </header>
</template>
