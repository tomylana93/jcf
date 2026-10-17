<script setup lang="ts">
import { ChevronRight } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

import AppNavLink from '@/components/layout/AppNavLink.vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarMenuAction,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useTranslations } from '@/composables/useTranslations';
import { flattenNavigationItems } from '@/lib/navigation';
import type { NavItem } from '@/types';

const props = defineProps<{ item: NavItem }>();

const { t } = useTranslations();

const { isCurrentUrl } = useCurrentUrl();

const { isMobile, state, setOpen, setOpenMobile } = useSidebar();

const active = computed(
    () => !props.item.external && isCurrentUrl(props.item.href),
);

const activeBranch = computed(() =>
    flattenNavigationItems([props.item]).some(
        (item) => !item.external && isCurrentUrl(item.href),
    ),
);

const expanded = ref(activeBranch.value);

watch(activeBranch, (value) => {
    if (value) {
        expanded.value = true;
    }
});

function expandSidebar(): void {
    if (!isMobile.value && state.value === 'collapsed') {
        setOpen(true);
    }
}
</script>

<template>
    <SidebarMenuItem>
        <Collapsible v-model:open="expanded">
            <SidebarMenuButton
                as-child
                :is-active="activeBranch"
                :tooltip="item.title"
            >
                <AppNavLink
                    :item="item"
                    :active="active"
                    :class="{
                        'min-h-11': isMobile,
                        'min-h-8': !isMobile,
                        'pr-12': isMobile && item.children?.length,
                    }"
                    @click="setOpenMobile(false)"
                />
            </SidebarMenuButton>
            <CollapsibleTrigger
                v-if="item.children?.length"
                as-child
            >
                <SidebarMenuAction
                    :aria-label="
                        t('navigation.label.toggle_submenu', {
                            title: props.item.title,
                        })
                    "
                    :class="isMobile ? 'top-0 size-11' : undefined"
                    @click="expandSidebar"
                >
                    <ChevronRight
                        :class="{ 'rotate-90': expanded }"
                        class="transition-transform motion-reduce:transition-none"
                        aria-hidden="true"
                    />
                </SidebarMenuAction>
            </CollapsibleTrigger>
            <CollapsibleContent v-if="item.children?.length">
                <SidebarMenuSub :class="{ 'gap-0 py-0': isMobile }">
                    <AppSidebarMenuItem
                        v-for="child in item.children"
                        :key="child.title"
                        :item="child"
                    />
                </SidebarMenuSub>
            </CollapsibleContent>
        </Collapsible>
    </SidebarMenuItem>
</template>
