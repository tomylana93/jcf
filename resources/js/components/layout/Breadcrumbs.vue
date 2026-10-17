<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import { computed } from 'vue';

import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslations } from '@/composables/useTranslations';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

type Props = {
    breadcrumbs: BreadcrumbItemType[];
};

const props = defineProps<Props>();

const { t } = useTranslations();
const ancestors = computed(() => props.breadcrumbs.slice(0, -1));
const current = computed(() => props.breadcrumbs.at(-1));
</script>

<template>
    <Breadcrumb class="min-w-0 flex-1">
        <BreadcrumbList class="flex-nowrap">
            <BreadcrumbItem
                v-if="ancestors.length"
                class="min-w-0 flex-1 sm:hidden"
            >
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="w-full min-w-0 justify-start px-1"
                            :aria-label="
                                t('navigation.label.breadcrumb_ancestors')
                            "
                        >
                            <span
                                class="truncate font-normal"
                                aria-current="page"
                                :title="current?.title"
                            >
                                {{ current?.title }}
                            </span>
                            <ChevronDown class="size-3 shrink-0" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="start"
                        class="max-w-[calc(100vw-2rem)]"
                    >
                        <DropdownMenuGroup>
                            <DropdownMenuItem
                                v-for="(item, index) in ancestors"
                                :key="index"
                                as-child
                            >
                                <Link
                                    :href="item.href"
                                    class="wrap-anywhere whitespace-normal"
                                >
                                    {{ item.title }}
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                    </DropdownMenuContent>
                </DropdownMenu>
            </BreadcrumbItem>
            <template
                v-for="(item, index) in breadcrumbs"
                :key="index"
            >
                <BreadcrumbItem
                    :class="
                        index === breadcrumbs.length - 1
                            ? ancestors.length
                                ? 'hidden min-w-0 flex-1 sm:inline-flex'
                                : 'min-w-0 flex-1'
                            : 'hidden max-w-40 min-w-0 shrink sm:inline-flex xl:max-w-64'
                    "
                >
                    <template v-if="index === breadcrumbs.length - 1">
                        <BreadcrumbPage
                            class="truncate"
                            :title="item.title"
                        >
                            {{ item.title }}
                        </BreadcrumbPage>
                    </template>
                    <template v-else>
                        <BreadcrumbLink as-child>
                            <Link
                                :href="item.href"
                                class="truncate"
                                :title="item.title"
                            >
                                {{ item.title }}
                            </Link>
                        </BreadcrumbLink>
                    </template>
                </BreadcrumbItem>
                <BreadcrumbSeparator
                    v-if="index !== breadcrumbs.length - 1"
                    class="hidden shrink-0 sm:block"
                />
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>
