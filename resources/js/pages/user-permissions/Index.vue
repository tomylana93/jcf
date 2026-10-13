<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';

import { DataTable } from '@/components/datatable';
import PageContainer from '@/components/layout/PageContainer.vue';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/permissions';
import type { PermissionUser, TablePaginator } from '@/types';

import { createColumns } from './columns';

const props = defineProps<{
    users: TablePaginator<PermissionUser>;
}>();
const { t } = useTranslations();
const columns = computed(() => createColumns(t));
watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('breadcrumb.permission'),
                href: index(),
            },
        ],
    }),
);
</script>

<template>
    <PageContainer
        :title="t('permission.card.title')"
        :description="t('permission.card.description')"
    >
        <Head :title="t('permission.title')" />
        <DataTable
            :url="index.url()"
            data-key="users"
            default-sort="name"
            :columns="columns"
            :rows="props.users.data"
            :row-count="props.users.total"
            :current-page="props.users.current_page"
            :page-size="props.users.per_page"
        />
    </PageContainer>
</template>
