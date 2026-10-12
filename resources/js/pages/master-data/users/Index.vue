<script setup lang="ts">
import { Head, useForm, setLayoutProps, usePage } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref, watchEffect } from 'vue';

import { updateStatus } from '@/actions/App/Http/Controllers/MasterData/UserController';
import { DataTable } from '@/components/datatable';
import PageContainer from '@/components/layout/PageContainer.vue';
import { useDateTime } from '@/composables/useDateTime';
import { useTranslations } from '@/composables/useTranslations';
import { index as masterDataIndex } from '@/routes/master-data';
import { index, exportMethod } from '@/routes/master-data/users';
import type {
    DataTableAction,
    DataTableFilter,
    SelectOption,
    TablePaginator,
    UserRow,
    UpdateUserStatusForm,
} from '@/types';

import { createColumns } from './columns';
import UserFormSheet from './UserFormSheet.vue';

const props = defineProps<{
    users: TablePaginator<UserRow>;
    roles: SelectOption[];
    statuses: SelectOption[];
}>();
const page = usePage();
const { t, locale } = useTranslations();
const { timezone } = useDateTime();
const canManage = computed(() =>
    page.props.auth.permissions.includes('users.manage'),
);
const selectedUser = ref<UserRow | null>(null);
const formOpen = ref(false);
const selectedUsers = ref<UserRow[]>([]);
const filters = computed<DataTableFilter[]>(() => [
    {
        key: 'role',
        label: t('user.label.role'),
        type: 'combobox',
        options: props.roles,
    },
    {
        key: 'status',
        label: t('user.label.status'),
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
                  onClick: create,
              },
          ]
        : [],
);
function create(): void {
    selectedUser.value = null;
    formOpen.value = true;
}
const columns = computed(() =>
    createColumns(
        t,
        locale.value,
        timezone.value,
        canManage.value,
        page.props.auth.user.id,
        edit,
        changeStatus,
    ),
);
watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('breadcrumb.master_data'),
                href: masterDataIndex(),
            },
            {
                title: t('breadcrumb.user'),
                href: index(),
            },
        ],
    });
});

function edit(user: UserRow): void {
    selectedUser.value = user;
    formOpen.value = true;
}
const statusForm = useForm<UpdateUserStatusForm>({
    status: 'active',
});
function changeStatus(user: UserRow): void {
    const status = user.status === 'active' ? 'disabled' : 'active';
    if (statusForm.processing) {
        return;
    }
    statusForm.status = status;
    statusForm.submit(updateStatus(user.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="t('user.title')" />
    <PageContainer
        :title="t('user.card.title')"
        :description="t('user.card.description')"
    >
        <DataTable
            v-model:selected-rows="selectedUsers"
            :url="index.url()"
            :export-url="exportMethod.url()"
            :export="true"
            data-key="users"
            :columns="columns"
            :filters="filters"
            :actions="actions"
            :rows="users.data"
            :row-count="users.total"
            :current-page="users.current_page"
            :page-size="users.per_page"
        />
        <UserFormSheet
            v-if="canManage"
            v-model:open="formOpen"
            :user="selectedUser"
            :roles="roles"
            :statuses="statuses"
        />
    </PageContainer>
</template>
