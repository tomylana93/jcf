<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { computed } from 'vue';

import { buttonVariants } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import type { TablePaginator } from '@/types';

const props = defineProps<{
    paginator: Pick<
        TablePaginator<unknown>,
        'current_page' | 'per_page' | 'total'
    >;
    label: string;
    pageHref: (page: number) => NonNullable<InertiaLinkProps['href']>;
}>();

const { t } = useTranslations();

const lastPage = computed(() =>
    Math.max(1, Math.ceil(props.paginator.total / props.paginator.per_page)),
);
const hasPrevious = computed(() => props.paginator.current_page > 1);
const hasNext = computed(() => props.paginator.current_page < lastPage.value);
</script>

<template>
    <nav
        v-if="lastPage > 1"
        class="flex items-center justify-between gap-3"
        :aria-label="label"
    >
        <Link
            v-if="hasPrevious"
            :href="pageHref(paginator.current_page - 1)"
            preserve-scroll
            :class="buttonVariants({ variant: 'outline', size: 'sm' })"
        >
            {{ t('table.pagination.button.previous') }}
        </Link>
        <span v-else />
        <p class="text-sm text-muted-foreground">
            {{
                t('table.pagination.helper.page_status', {
                    current: paginator.current_page,
                    total: lastPage,
                })
            }}
        </p>
        <Link
            v-if="hasNext"
            :href="pageHref(paginator.current_page + 1)"
            preserve-scroll
            :class="buttonVariants({ variant: 'outline', size: 'sm' })"
        >
            {{ t('table.pagination.button.next') }}
        </Link>
        <span v-else />
    </nav>
</template>
