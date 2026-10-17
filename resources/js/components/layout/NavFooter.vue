<script setup lang="ts">
import AppSidebarMenuItem from '@/components/layout/AppSidebarMenuItem.vue';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    useSidebar,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isMobile } = useSidebar();
</script>

<template>
    <SidebarGroup class="px-2">
        <SidebarGroupContent>
            <SidebarMenu :class="{ 'gap-0': isMobile }">
                <AppSidebarMenuItem
                    v-for="item in items"
                    :key="item.title"
                    :item="item"
                />
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
