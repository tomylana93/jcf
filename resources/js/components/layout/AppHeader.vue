<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AppNavLink from '@/components/layout/AppNavLink.vue';
import Breadcrumbs from '@/components/layout/Breadcrumbs.vue';
import UserMenuContent from '@/components/layout/UserMenuContent.vue';
import AppearanceSwitch from '@/components/shared/AppearanceSwitch.vue';
import AppLogo from '@/components/shared/AppLogo.vue';
import GlobalSearchTrigger from '@/components/shared/GlobalSearchTrigger.vue';
import HeaderPreferences from '@/components/shared/HeaderPreferences.vue';
import LocaleSelector from '@/components/shared/LocaleSelector.vue';
import NotificationBell from '@/components/shared/NotificationBell.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    NavigationMenuTrigger,
} from '@/components/ui/navigation-menu';
import { SidebarTrigger, useSidebar } from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { useTranslations } from '@/composables/useTranslations';
import {
    createAdminNavigationGroups,
    createFooterNavigationItems,
    createMainNavigationItems,
    flattenNavigationItems,
} from '@/lib/navigation';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, NavGroup } from '@/types';

const { t } = useTranslations();

const props = withDefaults(defineProps<{ breadcrumbs?: BreadcrumbItem[] }>(), {
    breadcrumbs: () => [],
});

const page = usePage();

const auth = computed(() => page.props.auth);

const { isCurrentUrl } = useCurrentUrl();

const { isMobile } = useSidebar();

// Mirrors NavMain: external links live in NavFooter only.
const mainItems = computed(() =>
    createMainNavigationItems(t).filter((item) => !item.external),
);

const adminGroups = computed(() =>
    createAdminNavigationGroups(
        t,
        page.props.auth.permissions.includes('settings.view'),
        page.props.auth.permissions.includes('users.view'),
        page.props.auth.super_admin &&
            page.props.auth.permissions.includes('users.permissions.assign'),
        page.props.auth.permissions.includes('website.content.edit') ||
            page.props.auth.permissions.includes('website.content.publish') ||
            page.props.auth.permissions.includes('website.configure') ||
            page.props.auth.permissions.includes('website.inbox.view'),
        page.props.auth.permissions.includes('documentation.manage'),
        page.props.auth.permissions.includes('audit.view'),
        page.props.auth.permissions.includes('system.view'),
        page.props.auth.permissions.includes('backups.manage'),
    ),
);

const footerItems = computed(() =>
    createFooterNavigationItems(
        t,
        auth.value.permissions.includes('documentation.view'),
        auth.value.permissions.includes('documentation.manage'),
    ),
);

function isGroupActive(group: NavGroup): boolean {
    return flattenNavigationItems(group.items).some(
        (item) => !item.external && isCurrentUrl(item.href),
    );
}
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80">
            <div
                class="mx-auto flex h-16 min-w-0 items-center gap-1 px-4 sm:gap-2"
            >
                <SidebarTrigger
                    v-if="isMobile"
                    :aria-label="t('navigation.label.open')"
                />
                <Link
                    :href="dashboard()"
                    class="hidden shrink-0 items-center gap-2 md:flex"
                >
                    <AppLogo />
                </Link>
                <nav
                    v-if="!isMobile"
                    :aria-label="t('navigation.label.main')"
                    class="min-w-0 flex-1 py-2"
                >
                    <NavigationMenu
                        class="mx-4"
                        :viewport="false"
                    >
                        <NavigationMenuList class="flex-wrap gap-2">
                            <NavigationMenuItem
                                v-for="item in mainItems"
                                :key="item.title"
                            >
                                <NavigationMenuLink
                                    as-child
                                    :active="isCurrentUrl(item.href)"
                                >
                                    <AppNavLink
                                        :item="item"
                                        :active="isCurrentUrl(item.href)"
                                        class="flex-row items-center gap-2"
                                    />
                                </NavigationMenuLink>
                            </NavigationMenuItem>
                            <NavigationMenuItem
                                v-for="(group, index) in adminGroups"
                                :key="group.title ?? `admin-${index}`"
                            >
                                <NavigationMenuTrigger
                                    :class="{
                                        'bg-accent/50 text-accent-foreground':
                                            isGroupActive(group),
                                    }"
                                >
                                    {{ group.title }}
                                </NavigationMenuTrigger>
                                <NavigationMenuContent class="z-50">
                                    <ul
                                        class="grid w-[min(480px,calc(100vw-2rem))] grid-cols-2 gap-1 p-2"
                                    >
                                        <li
                                            v-for="item in group.items"
                                            :key="item.title"
                                        >
                                            <NavigationMenuLink
                                                as-child
                                                :active="
                                                    isCurrentUrl(item.href)
                                                "
                                            >
                                                <AppNavLink
                                                    :item="item"
                                                    :active="
                                                        isCurrentUrl(item.href)
                                                    "
                                                    class="flex-row items-center gap-2"
                                                />
                                            </NavigationMenuLink>
                                        </li>
                                    </ul>
                                </NavigationMenuContent>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </nav>
                <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
                    <template v-if="!isMobile">
                        <Button
                            v-for="item in footerItems"
                            :key="item.title"
                            variant="ghost"
                            as-child
                        >
                            <AppNavLink
                                :item="item"
                                :active="
                                    !item.external && isCurrentUrl(item.href)
                                "
                                class="gap-2"
                            />
                        </Button>
                    </template>
                    <GlobalSearchTrigger />
                    <div class="hidden sm:block"><LocaleSelector /></div>
                    <NotificationBell />
                    <div class="hidden sm:block"><AppearanceSwitch /></div>
                    <div class="sm:hidden"><HeaderPreferences /></div>
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                :aria-label="t('navigation.label.account')"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar
                                    class="size-8 overflow-hidden rounded-full"
                                >
                                    <AvatarImage
                                        v-if="auth.user.avatar"
                                        :src="auth.user.avatar"
                                        :alt="auth.user.name"
                                    />
                                    <AvatarFallback
                                        class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            align="end"
                            class="w-56"
                        >
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>
        <div
            v-if="props.breadcrumbs.length > 0"
            class="flex w-full min-w-0 border-b border-sidebar-border/70"
        >
            <div
                class="mx-auto flex h-12 w-full min-w-0 items-center justify-start px-4"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>
</template>
