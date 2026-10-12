import {
    columnFilteringFeature,
    columnVisibilityFeature,
    globalFilteringFeature,
    rowPaginationFeature,
    rowSortingFeature,
    rowSelectionFeature,
    tableFeatures,
} from '@tanstack/vue-table';
import type { ColumnDef, RowData } from '@tanstack/vue-table';
import type { DataTableExportValue } from '@/types';

export const dataTableFeatures = tableFeatures({
    columnFilteringFeature,
    columnVisibilityFeature,
    globalFilteringFeature,
    rowPaginationFeature,
    rowSortingFeature,
    rowSelectionFeature,
});

export type DataTableColumnDef<TData extends RowData> = ColumnDef<
    typeof dataTableFeatures,
    TData
> & {
    exportable?: boolean;
    exportHeader?: string;
    exportValue?: (row: TData) => DataTableExportValue;
};
