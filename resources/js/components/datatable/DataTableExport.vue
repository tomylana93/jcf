<script setup lang="ts" generic="TData extends RowData">
import { Download, FileSpreadsheet, FileText } from '@lucide/vue';
import type { RowData, Table } from '@tanstack/vue-table';
import { computed } from 'vue';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslations } from '@/composables/useTranslations';

import type { dataTableFeatures } from './dataTable';
import { collectExportData, createExportMapper } from './exportData';
import { exportPreparing, prepareExport } from './exportJob';

const props = defineProps<{
    table: Table<typeof dataTableFeatures, TData>;
    filename: string;
    disabled: boolean;
    exportUrl?: string;
}>();

const { t } = useTranslations();

const data = computed(() => collectExportData(props.table));

const unavailable = computed(
    () =>
        props.disabled ||
        exportPreparing.value ||
        !data.value.rows.length ||
        !data.value.headers.length,
);

function generate(kind: 'xlsx' | 'pdf'): void {
    if (unavailable.value) {
        return;
    }
    const format = kind === 'xlsx' ? 'Excel' : 'PDF';

    const selected = props.table
        .getRowModel()
        .rows.some((row) => row.getIsSelected());

    const url = props.exportUrl;

    const mapper = createExportMapper(props.table);

    const snapshot = data.value;

    const records =
        !selected && url
            ? async () => {
                  const response = await fetch(url, {
                      headers: { Accept: 'application/json' },
                      cache: 'no-store',
                  });
                  if (!response.ok) {
                      throw new Error('Export data request failed');
                  }
                  const result: { data: TData[] } = await response.json();
                  return mapper(result.data);
              }
            : snapshot;
    void prepareExport(records, kind, props.filename, {
        preparing: t('table.export.status.preparing', { format }),
        ready: t('table.export.status.ready', { format }),
        download: t('table.export.button.download', { format }),
        error: t('table.export.error.failed'),
    });
}
</script>

<template>
    <div>
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    variant="outline"
                    size="default"
                    class="h-9"
                    :disabled="unavailable"
                    :title="t('table.export.helper.scope')"
                >
                    <Download data-icon="inline-start" />
                    {{ t('table.export.button.export') }}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuGroup>
                    <DropdownMenuItem
                        :disabled="unavailable"
                        @select="generate('xlsx')"
                    >
                        <FileSpreadsheet />
                        {{ t('table.export.label.excel') }}
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        :disabled="unavailable"
                        @select="generate('pdf')"
                    >
                        <FileText />
                        {{ t('table.export.label.pdf') }}
                    </DropdownMenuItem>
                </DropdownMenuGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
