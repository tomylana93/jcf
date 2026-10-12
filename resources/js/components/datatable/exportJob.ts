import { ref } from 'vue';
import { toast } from 'vue-sonner';
import type { DataTableExportData, DataTableExportMessages } from '@/types';
import { generateExcel, generatePdf } from './exportData';

export const exportPreparing = ref(false);

export async function prepareExport(
    data: DataTableExportData | (() => Promise<DataTableExportData>),
    kind: 'xlsx' | 'pdf',
    filename: string,
    messages: DataTableExportMessages,
): Promise<void> {
    if (exportPreparing.value) {
        return;
    }
    exportPreparing.value = true;
    let cleanup = () => {};

    const preparation = (async () => {
        await new Promise<void>((resolve) =>
            requestAnimationFrame(() => {
                setTimeout(resolve, 0);
            }),
        );
        const records = typeof data === 'function' ? await data() : data;

        const blob = await (kind === 'xlsx'
            ? generateExcel(records)
            : generatePdf(records));

        let url: string | null = URL.createObjectURL(blob);

        let cleanupTimer: ReturnType<typeof setTimeout> | undefined;
        cleanup = () => {
            clearTimeout(cleanupTimer);
            cleanupTimer = undefined;
            if (url !== null) {
                URL.revokeObjectURL(url);
                url = null;
            }
        };
        return {
            message: messages.ready,
            duration: Infinity,
            dismissible: true,
            closeButton: true,
            action: {
                label: messages.download,
                onClick: () => {
                    if (url === null) {
                        return;
                    }
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `${filename.replace(/[^a-zA-Z0-9_-]/g, '_')}.${kind}`;
                    try {
                        document.body.appendChild(link);
                        link.click();
                        cleanupTimer = setTimeout(cleanup, 1000);
                    } catch {
                        cleanup();
                        toast.error(messages.error);
                    } finally {
                        link.remove();
                    }
                },
            },
            onDismiss: cleanup,
            onAutoClose: cleanup,
        };
    })();
    try {
        await toast
            .promise(preparation, {
                loading: messages.preparing,
                dismissible: false,
                success: (notification: Awaited<typeof preparation>) =>
                    notification,
                error: () => {
                    cleanup();
                    return {
                        message: messages.error,
                        dismissible: true,
                        closeButton: true,
                    };
                },
            })
            ?.unwrap();
    } catch {
        // The promise toast displays preparation failures.
    } finally {
        exportPreparing.value = false;
    }
}
