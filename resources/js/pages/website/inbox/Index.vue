<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';

import { DataTable } from '@/components/datatable';
import PageContainer from '@/components/layout/PageContainer.vue';
import { useDateTime } from '@/composables/useDateTime';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/website/inbox';

import { createColumns } from './columns';
import type { ContactRow } from './columns';

defineProps<{
    messages: {
        data: ContactRow[];
        meta: { total: number; current_page: number; per_page: number };
    };
}>();
const { t } = useTranslations();
const { formatDateTime } = useDateTime();
const columns = computed(() => createColumns(t, formatDateTime));
watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [{ title: t('contact.title.inbox'), href: index() }],
    }),
);
</script>
<template>
    <Head :title="t('contact.title.inbox')" />

    <PageContainer
        :title="t('contact.title.inbox')"
        :description="t('contact.helper.inbox')"
    >
        <DataTable
            :url="index().url"
            data-key="messages"
            :columns="columns"
            :rows="messages.data"
            :row-count="messages.meta.total"
            :current-page="messages.meta.current_page"
            :page-size="messages.meta.per_page"
        />
    </PageContainer>
</template>
