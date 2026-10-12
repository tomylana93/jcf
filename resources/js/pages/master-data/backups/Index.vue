<script setup lang="ts">
import { Head, setLayoutProps, useForm, usePoll } from '@inertiajs/vue3';
import { Plus, Upload } from '@lucide/vue';
import { computed, h, ref, watchEffect } from 'vue';

import {
    store,
    upload,
    restore,
    destroy,
} from '@/actions/App/Http/Controllers/MasterData/BackupController';
import { DataTable } from '@/components/datatable';
import type { DataTableColumnDef } from '@/components/datatable';
import PageContainer from '@/components/layout/PageContainer.vue';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { useDateTime } from '@/composables/useDateTime';
import { useTranslations } from '@/composables/useTranslations';
import { index as masterDataIndex } from '@/routes/master-data';
import { index } from '@/routes/master-data/backups';
import type {
    BackupPageProps,
    DataTableAction,
    RestoreBackupForm,
    BackupArchive,
    UploadBackupForm,
} from '@/types';

import BackupActions from './BackupActions.vue';
const props = defineProps<BackupPageProps>();
const { t } = useTranslations();
const { formatDateTime } = useDateTime();
const createForm = useForm({});
const uploadForm = useForm<UploadBackupForm>({ archive: null });
const restoreForm = useForm<RestoreBackupForm>({ confirmation: true });
const uploadOpen = ref(false);
const selectedBackup = ref<BackupArchive | null>(null);
const restoreOpen = ref(false);
const deleteOpen = ref(false);
const deleteForm = useForm({});
const busy = computed(
    () => props.backupStatus === 'running' || props.restoreStatus === 'running',
);
const actions = computed<DataTableAction[]>(() => [
    {
        label: t('backup.button.backup'),
        icon: Plus,
        disabled:
            !props.backupConfigured || busy.value || createForm.processing,
        onClick: () => createForm.submit(store(), { preserveScroll: true }),
    },
    {
        label: t('backup.button.upload'),
        icon: Upload,
        variant: 'outline',
        disabled: !props.backupConfigured || busy.value,
        onClick: () => {
            uploadForm.reset();
            uploadForm.clearErrors();
            uploadOpen.value = true;
        },
    },
]);
const columns = computed<DataTableColumnDef<BackupArchive>[]>(() => [
    {
        accessorKey: 'filename',
        header: t('backup.label.filename'),
        cell: ({ row }) =>
            h('span', { class: 'wrap-anywhere' }, row.original.filename),
    },
    {
        accessorKey: 'size',
        header: t('backup.label.size'),
        cell: ({ row }) => `${(row.original.size / 1048576).toFixed(2)} MB`,
    },
    {
        accessorKey: 'created_at',
        header: t('backup.label.created_at'),
        cell: ({ row }) => formatDateTime(row.original.created_at),
    },
    {
        id: 'actions',
        header: t('table.label.actions'),
        enableSorting: false,
        enableHiding: false,
        cell: ({ row }) =>
            h(BackupActions, {
                backup: row.original,
                disabled: !props.backupConfigured || busy.value,
                deleteDisabled: busy.value || deleteForm.processing,
                onDelete: () => {
                    selectedBackup.value = row.original;
                    deleteForm.clearErrors();
                    deleteOpen.value = true;
                },
                onRestore: () => {
                    selectedBackup.value = row.original;
                    restoreForm.clearErrors();
                    restoreOpen.value = true;
                },
            }),
    },
]);
watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [
            { title: t('breadcrumb.master_data'), href: masterDataIndex() },
            { title: t('backup.title'), href: index() },
        ],
    }),
);
usePoll(15000, { only: ['backups', 'backupStatus', 'restoreStatus'] });
function submitUpload(): void {
    uploadForm.submit(upload(), {
        preserveScroll: true,
        onSuccess: () => {
            uploadOpen.value = false;
            uploadForm.reset();
        },
    });
}
function submitRestore(): void {
    if (selectedBackup.value) {
        restoreForm.submit(restore(selectedBackup.value.filename), {
            preserveScroll: true,
            onSuccess: () => {
                restoreOpen.value = false;
            },
        });
    }
}
function submitDelete(): void {
    if (selectedBackup.value) {
        deleteForm.submit(destroy(selectedBackup.value.filename), {
            preserveScroll: true,
            onSuccess: () => {
                deleteOpen.value = false;
            },
        });
    }
}
function selectArchive(event: Event): void {
    uploadForm.archive = (event.target as HTMLInputElement).files?.[0] ?? null;
}
</script>
<template>
    <PageContainer
        :title="t('backup.title')"
        :description="t('backup.card.description')"
    >
        <Head :title="t('backup.title')" />
        <div class="flex flex-wrap items-center gap-3">
            <Badge variant="secondary"
                >{{ t('backup.label.backup') }}:
                {{ t(`backup.backup_status.${backupStatus}`) }}</Badge
            >
            <Badge variant="secondary"
                >{{ t('backup.label.restore') }}:
                {{ t(`backup.backup_status.${restoreStatus}`) }}</Badge
            >
        </div>
        <p v-if="!backupConfigured" class="text-sm text-muted-foreground">
            {{ t('backup.helper.setup') }}
        </p>
        <p v-if="restoreStatus === 'failed'" class="text-sm text-destructive">
            {{ t('backup.error.restore_failed') }}
        </p>
        <FieldError :errors="Object.values(createForm.errors)" />
        <DataTable
            :url="index.url()"
            data-key="backups"
            :columns="columns"
            :actions="actions"
            :rows="backups.data"
            :row-count="backups.total"
            :current-page="backups.current_page"
            :page-size="backups.per_page"
        />
        <Dialog v-model:open="uploadOpen">
            <DialogContent>
                <DialogHeader
                    ><DialogTitle>{{ t('backup.button.upload') }}</DialogTitle
                    ><DialogDescription>{{
                        t('backup.helper.upload')
                    }}</DialogDescription></DialogHeader
                >
                <form
                    novalidate
                    class="flex flex-1 flex-col gap-4 p-4"
                    @submit.prevent="submitUpload"
                >
                    <Field
                        ><FieldLabel for="backup-archive">{{
                            t('backup.label.archive')
                        }}</FieldLabel
                        ><Input
                            id="backup-archive"
                            type="file"
                            accept=".zip"
                            @change="selectArchive" /><FieldError
                            :errors="Object.values(uploadForm.errors)"
                    /></Field>
                    <p v-if="uploadForm.progress" class="text-sm">
                        {{ uploadForm.progress.percentage }}%
                    </p>
                    <DialogFooter
                        ><Button
                            type="submit"
                            :disabled="uploadForm.processing"
                            >{{ t('backup.button.upload') }}</Button
                        ></DialogFooter
                    >
                </form>
            </DialogContent>
        </Dialog>
        <AlertDialog v-model:open="restoreOpen">
            <AlertDialogContent>
                <AlertDialogHeader
                    ><AlertDialogTitle>{{
                        t('backup.card.restore')
                    }}</AlertDialogTitle
                    ><AlertDialogDescription>{{
                        t('backup.helper.restore', {
                            filename: selectedBackup?.filename ?? '',
                        })
                    }}</AlertDialogDescription></AlertDialogHeader
                >
                <form novalidate @submit.prevent="submitRestore">
                    <FieldError :errors="Object.values(restoreForm.errors)" />
                    <AlertDialogFooter
                        ><AlertDialogCancel>{{
                            t('common.button.cancel')
                        }}</AlertDialogCancel
                        ><Button
                            type="submit"
                            variant="destructive"
                            :disabled="restoreForm.processing"
                            >{{ t('backup.button.restore') }}</Button
                        ></AlertDialogFooter
                    >
                </form>
            </AlertDialogContent>
        </AlertDialog>
        <AlertDialog v-model:open="deleteOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{{
                        t('backup.card.delete')
                    }}</AlertDialogTitle>
                    <AlertDialogDescription>{{
                        t('backup.helper.delete', {
                            filename: selectedBackup?.filename ?? '',
                        })
                    }}</AlertDialogDescription>
                </AlertDialogHeader>
                <form novalidate @submit.prevent="submitDelete">
                    <FieldError :errors="Object.values(deleteForm.errors)" />
                    <AlertDialogFooter>
                        <AlertDialogCancel :disabled="deleteForm.processing">{{
                            t('common.button.cancel')
                        }}</AlertDialogCancel>
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="deleteForm.processing"
                            >{{ t('common.button.delete') }}</Button
                        >
                    </AlertDialogFooter>
                </form>
            </AlertDialogContent>
        </AlertDialog>
    </PageContainer>
</template>
