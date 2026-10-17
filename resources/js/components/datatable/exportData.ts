import type { RowData, Table } from '@tanstack/vue-table';

import type { DataTableExportData, DataTableExportValue } from '@/types';

import type { DataTableColumnDef, dataTableFeatures } from './dataTable';

export function createExportMapper<TData extends RowData>(
    table: Table<typeof dataTableFeatures, TData>,
): (rows: TData[]) => DataTableExportData {
    const columns = table.getVisibleLeafColumns().filter((column) => {
        const definition = column.columnDef as DataTableColumnDef<TData>;
        return (
            definition.exportable !== false &&
            (definition.exportValue !== undefined ||
                column.accessorFn !== undefined)
        );
    });
    return (rows) => ({
        headers: columns.map((column) => {
            const definition = column.columnDef as DataTableColumnDef<TData>;
            return (
                definition.exportHeader ??
                (typeof definition.header === 'string'
                    ? definition.header
                    : column.id)
            );
        }),
        rows: rows.map((row, index) =>
            columns.map((column): DataTableExportValue => {
                const definition =
                    column.columnDef as DataTableColumnDef<TData>;

                const value = definition.exportValue
                    ? definition.exportValue(row)
                    : column.accessorFn?.(row, index);
                if (value == null) {
                    return null;
                }
                if (
                    value instanceof Date ||
                    typeof value === 'string' ||
                    typeof value === 'number' ||
                    typeof value === 'boolean'
                ) {
                    return value;
                }
                return JSON.stringify(value);
            }),
        ),
    });
}

export function collectExportData<TData extends RowData>(
    table: Table<typeof dataTableFeatures, TData>,
): DataTableExportData {
    const rows = table.getRowModel().rows;

    const selected = rows.filter((row) => row.getIsSelected());
    return createExportMapper(table)(
        (selected.length ? selected : rows).map((row) => row.original),
    );
}

export async function generateExcel(data: DataTableExportData): Promise<Blob> {
    const { default: ExcelJS } = await import('exceljs');

    const workbook = new ExcelJS.Workbook();

    const worksheet = workbook.addWorksheet('Data');
    worksheet.addRow(data.headers);
    worksheet.addRows(data.rows);
    worksheet.getRow(1).font = { bold: true };
    worksheet.views = [{ state: 'frozen', ySplit: 1 }];
    worksheet.columns.forEach((column) => {
        column.width = 24;
    });
    const buffer = await workbook.xlsx.writeBuffer();
    return new Blob([new Uint8Array(buffer)], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
}

export async function generatePdf(data: DataTableExportData): Promise<Blob> {
    const [{ jsPDF }, { autoTable }] = await Promise.all([
        import('jspdf'),
        import('jspdf-autotable'),
    ]);

    const document = new jsPDF({ orientation: 'landscape' });
    autoTable(document, {
        head: [data.headers],
        body: data.rows.map((row) =>
            row.map((value) =>
                value instanceof Date
                    ? value.toISOString()
                    : String(value ?? ''),
            ),
        ),
        showHead: 'everyPage',
        styles: { overflow: 'linebreak', fontSize: 9 },
    });
    return document.output('blob');
}
