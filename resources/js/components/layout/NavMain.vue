<script setup lang="ts">
import { computed } from 'vue';

import AppSidebarMenuItem from '@/components/layout/AppSidebarMenuItem.vue';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    useSidebar,
} from '@/components/ui/sidebar';
import { useTranslations } from '@/composables/useTranslations';
import type { NavGroup } from '@/types';

const props = defineProps<{
    groups: NavGroup[];
}>();

const { t } = useTranslations();

const { isMobile } = useSidebar();

// External links are only supported in NavFooter; NavMain renders internal items.
// Groups are shown on desktop and mobile alike.
const visibleGroups = computed(() =>
    props.groups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => !item.external),
        }))
        .filter((group) => group.items.length > 0),
);
</script>

<template>
    <nav :aria-label="t('navigation.label.main')">
        <SidebarGroup
            v-for="(group, index) in visibleGroups"
            :key="group.title ?? `group-${index}`"
            class="px-2"
            :class="{ 'py-0': isMobile }"
        >
            <SidebarGroupLabel v-if="group.title">
                {{ group.title }}
            </SidebarGroupLabel>
            <SidebarMenu :class="{ 'gap-0': isMobile }">
                <AppSidebarMenuItem
                    v-for="item in group.items"
                    :key="item.title"
                    :item="item"
                />
            </SidebarMenu>
        </SidebarGroup>
    </nav>
</template>
