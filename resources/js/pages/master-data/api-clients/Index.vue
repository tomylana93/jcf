<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref, watchEffect } from 'vue';

import { DataTable } from '@/components/datatable';
import PageContainer from '@/components/layout/PageContainer.vue';
import { useDateTime } from '@/composables/useDateTime';
import { usePermissions } from '@/composables/usePermissions';
import { useTranslations } from '@/composables/useTranslations';
import { index as masterDataIndex } from '@/routes/master-data';
import { exportMethod, index } from '@/routes/master-data/api-clients';
import type {
    ApiClientRow,
    DataTableAction,
    DataTableFilter,
    SelectOption,
    TablePaginator,
} from '@/types';

import ApiClientFormSheet from './ApiClientFormSheet.vue';
import { createColumns } from './columns';

const props = defineProps<{
    clients: TablePaginator<ApiClientRow>;
    statuses: SelectOption[];
}>();

const { can } = usePermissions();
const { t } = useTranslations();
const { formatDateTime } = useDateTime();

const canManage = computed(() => can('api-clients.manage'));
const formOpen = ref(false);

const filters = computed<DataTableFilter[]>(() => [
    {
        key: 'status',
        label: t('api_client.label.status'),
        type: 'select',
        options: props.statuses,
    },
]);

const actions = computed<DataTableAction[]>(() =>
    canManage.value
        ? [
              {
                  label: t('common.button.create'),
                  icon: Plus,
                  onClick: () => (formOpen.value = true),
              },
          ]
        : [],
);

const columns = computed(() => createColumns({ t, formatDateTime }));

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: t('breadcrumb.master_data'), href: masterDataIndex() },
            { title: t('breadcrumb.api_client'), href: index() },
        ],
    });
});
</script>

<template>
    <Head :title="t('api_client.title')" />

    <PageContainer
        :title="t('api_client.card.title')"
        :description="t('api_client.card.description')"
    >
        <DataTable
            :url="index.url()"
            :export-url="exportMethod.url()"
            :export="true"
            data-key="clients"
            :columns="columns"
            :filters="filters"
            :actions="actions"
            :rows="clients.data"
            :row-count="clients.total"
            :current-page="clients.current_page"
            :page-size="clients.per_page"
        />
        <ApiClientFormSheet
            v-if="canManage"
            v-model:open="formOpen"
            :client="null"
            :statuses="statuses"
        />
    </PageContainer>
</template>
