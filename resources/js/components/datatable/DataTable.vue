<script setup lang="ts" generic="TData extends RowData">
import { router, usePage } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown, Search } from '@lucide/vue';
import { FlexRender, useTable } from '@tanstack/vue-table';
import type {
    PaginationState,
    RowData,
    RowSelectionState,
    SortingState,
    Updater,
    ColumnVisibilityState,
} from '@tanstack/vue-table';
import { useDebounceFn } from '@vueuse/core';
import { computed, onBeforeUnmount, ref, toRef, watch } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
} from '@/components/ui/card';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useTranslations } from '@/composables/useTranslations';
import type { DataTableAction, DataTableFilter } from '@/types';

import { dataTableFeatures, type DataTableColumnDef } from './dataTable';
import DataTableExport from './DataTableExport.vue';
import DataTablePagination from './DataTablePagination.vue';
import DataTableToolbar from './DataTableToolbar.vue';

const props = withDefaults(
    defineProps<{
        columns: DataTableColumnDef<TData>[];
        rows: TData[];
        rowCount: number;
        currentPage: number;
        pageSize: number;
        url: string;
        exportUrl?: string;
        export?: boolean;
        defaultSort?: string;
        dataKey: string;
        filters?: DataTableFilter[];
        actions?: DataTableAction[];
        loading?: boolean;
        searchPlaceholder?: string;
        pageSizeOptions?: number[];
    }>(),
    {
        export: false,
        defaultSort: '-created_at',
        filters: () => [],
        actions: () => [],
        loading: false,
        pageSizeOptions: () => [10, 25, 50, 100],
    },
);

const selectedRows = defineModel<TData[]>('selectedRows', {
    default: () => [],
});

const { t } = useTranslations();

const page = usePage();

const currentUrl = new URL(page.url, 'http://localhost');

const filterValues = ref<Record<string, string>>(
    Object.fromEntries(
        props.filters.map((filter) => [
            filter.key,
            currentUrl.searchParams.get(`filter[${filter.key}]`) ?? '',
        ]),
    ),
);

const initialSort = currentUrl.searchParams.get('sort') ?? props.defaultSort;

const pagination = ref<PaginationState>({
    pageIndex: props.currentPage - 1,
    pageSize: props.pageSize,
});

const sorting = ref<SortingState>([
    { id: initialSort.replace(/^-/, ''), desc: initialSort.startsWith('-') },
]);

const rowSelection = ref<RowSelectionState>({});

const columnVisibility = ref<ColumnVisibilityState>({});
watch(
    () => props.rows,
    () => {
        rowSelection.value = {};
    },
    { flush: 'sync' },
);
const globalFilter = ref(currentUrl.searchParams.get('filter[search]') ?? '');

const columnDefinitions = toRef(props, 'columns');

const rows = toRef(props, 'rows');

const rowCount = toRef(props, 'rowCount');

function resolveUpdater<TValue>(
    updater: Updater<TValue>,
    current: TValue,
): TValue {
    return typeof updater === 'function'
        ? (updater as (value: TValue) => TValue)(current)
        : updater;
}

let active = true;

const tablePath = new URL(props.url, 'http://localhost').pathname;

const stopBefore = router.on('before', (event) => {
    if (event.detail.visit.url.pathname !== tablePath) active = false;
});

const stopFinish = router.on('finish', () => {
    active = new URL(page.url, 'http://localhost').pathname === tablePath;
});
onBeforeUnmount(() => {
    active = false;
    stopBefore();
    stopFinish();
});

function reload(): void {
    if (!active) return;
    pendingReload.value = true;
    const url = new URL(props.url, 'http://localhost');

    const sort = sorting.value[0];
    url.searchParams.set('page', String(pagination.value.pageIndex + 1));
    url.searchParams.set('per_page', String(pagination.value.pageSize));
    if (sort) {
        url.searchParams.set('sort', `${sort.desc ? '-' : ''}${sort.id}`);
    }
    for (const filter of props.filters) {
        const key = `filter[${filter.key}]`;

        const value = filterValues.value[filter.key];
        if (value) {
            url.searchParams.set(key, value);
        } else {
            url.searchParams.delete(key);
        }
    }
    url.searchParams.delete('filter[search]');
    if (globalFilter.value) {
        url.searchParams.set('filter[search]', globalFilter.value);
    }
    router.get(
        url.pathname + url.search,
        {},
        {
            only: [props.dataKey],
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onFinish: () => {
                pendingReload.value = false;
            },
        },
    );
}

const exportRequestUrl = computed(() => {
    if (!props.exportUrl) {
        return undefined;
    }
    const url = new URL(props.exportUrl, 'http://localhost');

    const current = new URL(page.url, 'http://localhost');
    for (const [key, value] of current.searchParams) {
        if (key.startsWith('filter[') || key === 'sort') {
            url.searchParams.set(key, value);
        }
    }
    return url.pathname + url.search;
});

const pendingReload = ref(false);

const reloadSearch = useDebounceFn(reload, 300);

function updatePagination(value: PaginationState): void {
    pagination.value = value;
    reload();
}

function updateSorting(value: SortingState): void {
    sorting.value = value;
    pagination.value.pageIndex = 0;
    reload();
}

function updateTableFilter(key: string, value: string): void {
    filterValues.value[key] = value;
    pagination.value.pageIndex = 0;
    reload();
}

function updateFilter(value: string): void {
    pendingReload.value = true;
    globalFilter.value = value;
    pagination.value.pageIndex = 0;
    void reloadSearch();
}

const table = useTable<typeof dataTableFeatures, TData>({
    features: dataTableFeatures,
    columns: columnDefinitions,
    data: rows,
    rowCount,
    state: {
        get columnVisibility() {
            return columnVisibility.value;
        },
        get rowSelection() {
            return rowSelection.value;
        },
        get globalFilter() {
            return globalFilter.value;
        },
        get pagination() {
            return pagination.value;
        },
        get sorting() {
            return sorting.value;
        },
    },
    onGlobalFilterChange: (updater) => {
        updateFilter(resolveUpdater(updater, globalFilter.value));
    },
    onPaginationChange: (updater) => {
        updatePagination(resolveUpdater(updater, pagination.value));
    },
    onSortingChange: (updater) => {
        updateSorting(resolveUpdater(updater, sorting.value));
    },
    onColumnVisibilityChange: (updater) => {
        columnVisibility.value = resolveUpdater(
            updater,
            columnVisibility.value,
        );
    },
    onRowSelectionChange: (updater) => {
        rowSelection.value = resolveUpdater(updater, rowSelection.value);
    },
    manualFiltering: true,
    manualPagination: true,
    manualSorting: true,
});

watch(
    () => [rowSelection.value, props.rows],
    () => {
        selectedRows.value = table
            .getSelectedRowModel()
            .rows.map((row) => row.original);
    },
    { flush: 'sync' },
);

const search = computed({
    get: () => globalFilter.value,
    set: (value: string) => {
        table.setGlobalFilter(value);
    },
});
</script>

<template>
    <Card class="@container/datatable w-full min-w-0">
        <CardHeader class="min-w-0 gap-4">
            <DataTableToolbar
                :table="table"
                v-model:search="search"
                :filters="filters"
                :filter-values="filterValues"
                :actions="actions"
                @update:filter="updateTableFilter"
                :search-placeholder="
                    searchPlaceholder ?? t('table.placeholder.search')
                "
            >
                <DataTableExport
                    v-if="props.export"
                    :table="table"
                    :filename="dataKey"
                    :export-url="exportRequestUrl"
                    :disabled="loading || pendingReload"
                />
                <slot name="actions" />
            </DataTableToolbar>
        </CardHeader>

        <CardContent class="min-w-0 px-0">
            <div class="overflow-x-auto border-y">
                <Table>
                    <TableHeader>
                        <TableRow
                            v-for="headerGroup in table.getHeaderGroups()"
                            :key="headerGroup.id"
                            class="bg-muted/40"
                        >
                            <TableHead
                                v-for="header in headerGroup.headers"
                                :key="header.id"
                            >
                                <Button
                                    v-if="header.column.getCanSort()"
                                    variant="ghost"
                                    size="sm"
                                    class="-ml-3"
                                    @click="
                                        header.column.getToggleSortingHandler()?.(
                                            $event,
                                        )
                                    "
                                >
                                    <FlexRender :header="header" />
                                    <ArrowUp
                                        v-if="
                                            header.column.getIsSorted() ===
                                            'asc'
                                        "
                                        data-icon="inline-end"
                                    />
                                    <ArrowDown
                                        v-else-if="
                                            header.column.getIsSorted() ===
                                            'desc'
                                        "
                                        data-icon="inline-end"
                                    />
                                    <ArrowUpDown
                                        v-else
                                        data-icon="inline-end"
                                    />
                                </Button>
                                <FlexRender v-else :header="header" />
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="loading">
                            <TableRow
                                v-for="row in pagination.pageSize"
                                :key="row"
                            >
                                <TableCell
                                    v-for="column in table.getVisibleLeafColumns()"
                                    :key="column.id ?? row"
                                >
                                    <Skeleton class="h-5 w-full" />
                                </TableCell>
                            </TableRow>
                        </template>
                        <template v-else-if="table.getRowModel().rows.length">
                            <TableRow
                                v-for="row in table.getRowModel().rows"
                                :key="row.id"
                            >
                                <TableCell
                                    v-for="cell in row.getVisibleCells()"
                                    :key="cell.id"
                                >
                                    <FlexRender :cell="cell" />
                                </TableCell>
                            </TableRow>
                        </template>
                        <TableRow v-else>
                            <TableCell
                                :colspan="table.getVisibleLeafColumns().length"
                            >
                                <Empty>
                                    <EmptyHeader>
                                        <EmptyMedia variant="icon">
                                            <Search />
                                        </EmptyMedia>
                                        <EmptyTitle>{{
                                            t('table.helper.empty')
                                        }}</EmptyTitle>
                                        <EmptyDescription>
                                            {{
                                                t(
                                                    'table.helper.empty_description',
                                                )
                                            }}
                                        </EmptyDescription>
                                    </EmptyHeader>
                                </Empty>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </CardContent>

        <CardFooter>
            <DataTablePagination
                :pagination="pagination"
                :row-count="rowCount"
                :visible-rows="rows.length"
                :selected-count="selectedRows.length"
                :page-size-options="pageSizeOptions"
                @update:pagination="updatePagination"
            />
        </CardFooter>
    </Card>
</template>
