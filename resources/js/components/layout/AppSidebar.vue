<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { X } from '@lucide/vue';
import { computed, watch } from 'vue';

import NavFooter from '@/components/layout/NavFooter.vue';
import NavMain from '@/components/layout/NavMain.vue';
import NavUser from '@/components/layout/NavUser.vue';
import AppLogo from '@/components/shared/AppLogo.vue';
import { Button } from '@/components/ui/button';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useTranslations } from '@/composables/useTranslations';
import {
    createFooterNavigationItems,
    createNavigationGroups,
} from '@/lib/navigation';
import { dashboard } from '@/routes';

const { t } = useTranslations();

const navigationGroups = computed(() =>
    createNavigationGroups(
        t,
        page.props.auth.permissions.includes('settings.view'),
        page.props.auth.permissions.includes('users.view'),
        page.props.auth.super_admin &&
            page.props.auth.permissions.includes('users.permissions.assign'),
        page.props.auth.permissions.includes('documentation.view'),
        page.props.auth.permissions.includes('documentation.manage'),
        page.props.auth.permissions.includes('audit.view'),
        page.props.auth.permissions.includes('system.view'),
        page.props.auth.permissions.includes('backups.manage'),
    ),
);

const footerItems = computed(() =>
    createFooterNavigationItems(
        t,
        page.props.auth.permissions.includes('documentation.view'),
        page.props.auth.permissions.includes('documentation.manage'),
    ),
);

defineProps<{ mobileOnly?: boolean }>();

const { isMobile, setOpenMobile } = useSidebar();

const page = usePage();

watch(
    () => page.url,
    () => setOpenMobile(false),
);

watch(isMobile, () => setOpenMobile(false));
</script>

<template>
    <Sidebar
        v-if="!mobileOnly || isMobile"
        collapsible="icon"
        variant="inset"
    >
        <SidebarHeader>
            <Button
                v-if="isMobile"
                variant="ghost"
                size="icon"
                class="ml-auto size-11"
                :aria-label="t('navigation.label.close')"
                @click="setOpenMobile(false)"
            >
                <X aria-hidden="true" />
            </Button>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                    >
                        <Link
                            :href="dashboard()"
                            @click="setOpenMobile(false)"
                        >
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :groups="navigationGroups" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter
                v-if="footerItems.length > 0"
                :items="footerItems"
            />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
