<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';

import { DataTable } from '@/components/datatable';
import PageContainer from '@/components/layout/PageContainer.vue';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useDateTime } from '@/composables/useDateTime';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/audit';
import type { AuditLogRow, DataTableFilter, TablePaginator } from '@/types';

import { createColumns } from './columns';

defineProps<{ logs: TablePaginator<AuditLogRow> }>();

const { t } = useTranslations();
const { formatDateTime } = useDateTime();

const selected = ref<AuditLogRow | null>(null);
const detailOpen = ref(false);

const fields = computed(() =>
    selected.value
        ? [
              ...new Set([
                  ...Object.keys(selected.value.before),
                  ...Object.keys(selected.value.after),
              ]),
          ]
        : [],
);

const columns = computed(() => createColumns(t, formatDateTime, showDetail));

const filters = computed<DataTableFilter[]>(() => [
    {
        key: 'event',
        label: t('audit.label.event'),
        type: 'select',
        options: [
            { value: 'user.created', label: t('audit.event.user.created') },
            { value: 'user.updated', label: t('audit.event.user.updated') },
            {
                value: 'user.status_updated',
                label: t('audit.event.user.status_updated'),
            },
            {
                value: 'user.permissions_updated',
                label: t('audit.event.user.permissions_updated'),
            },
            {
                value: 'user.invitation_queued',
                label: t('audit.event.user.invitation_queued'),
            },
            {
                value: 'settings.updated',
                label: t('audit.event.settings.updated'),
            },
        ],
    },
]);

function showDetail(log: AuditLogRow): void {
    selected.value = log;
    detailOpen.value = true;
}

function displayValue(value: unknown): string {
    if (value === undefined || value === null) {
        return '—';
    }
    if (typeof value === 'string') {
        return value;
    }
    return JSON.stringify(value);
}

watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [{ title: t('breadcrumb.audit'), href: index() }],
    }),
);
</script>

<template>
    <Head :title="t('audit.title')" />

    <PageContainer
        :title="t('audit.card.title')"
        :description="t('audit.card.description')"
    >
        <DataTable
            :url="index.url()"
            data-key="logs"
            :columns="columns"
            :filters="filters"
            :rows="logs.data"
            :row-count="logs.total"
            :current-page="logs.current_page"
            :page-size="logs.per_page"
        />
        <Sheet v-model:open="detailOpen">
            <SheetContent class="overflow-y-auto sm:max-w-xl">
                <SheetHeader>
                    <SheetTitle>
                        {{ t('audit.card.detail_title') }}
                    </SheetTitle>
                    <SheetDescription>
                        {{ selected?.event_label }} —
                        {{ selected?.subject_label }}
                    </SheetDescription>
                </SheetHeader>
                <div
                    v-if="selected"
                    class="space-y-6 px-4 pb-6"
                >
                    <dl class="grid gap-3 text-sm">
                        <div>
                            <dt class="text-muted-foreground">
                                {{ t('audit.label.actor') }}
                            </dt>
                            <dd>{{ selected.actor_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">
                                {{ t('audit.label.time') }}
                            </dt>
                            <dd>
                                {{ formatDateTime(selected.created_at) }}
                            </dd>
                        </div>
                    </dl>
                    <div
                        v-for="field in fields"
                        :key="field"
                        class="space-y-2 rounded-lg border p-3"
                    >
                        <p class="text-sm font-medium wrap-break-word">
                            {{ field }}
                        </p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <p class="mb-1 text-xs text-muted-foreground">
                                    {{ t('audit.label.before') }}
                                </p>
                                <pre
                                    class="rounded bg-muted/50 p-2 text-xs wrap-anywhere whitespace-pre-wrap"
                                    >{{
                                        displayValue(selected.before[field])
                                    }}</pre>
                            </div>
                            <div>
                                <p class="mb-1 text-xs text-muted-foreground">
                                    {{ t('audit.label.after') }}
                                </p>
                                <pre
                                    class="rounded bg-muted/50 p-2 text-xs wrap-anywhere whitespace-pre-wrap"
                                    >{{
                                        displayValue(selected.after[field])
                                    }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    </PageContainer>
</template>
