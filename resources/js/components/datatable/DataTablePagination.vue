<script setup lang="ts">
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from '@lucide/vue';
import type { PaginationState } from '@tanstack/vue-table';
import { computed } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();

const props = defineProps<{
    pagination: PaginationState;
    rowCount: number;
    visibleRows: number;
    selectedCount?: number;
    pageSizeOptions: number[];
}>();

const emit = defineEmits<{ 'update:pagination': [value: PaginationState] }>();

const currentPage = computed(() => props.pagination.pageIndex + 1);

const pageCount = computed(() =>
    Math.max(Math.ceil(props.rowCount / props.pagination.pageSize), 1),
);

const displayFrom = computed(() =>
    props.rowCount === 0
        ? 0
        : props.pagination.pageIndex * props.pagination.pageSize + 1,
);

const displayTo = computed(() =>
    props.rowCount === 0
        ? 0
        : Math.min(displayFrom.value + props.visibleRows - 1, props.rowCount),
);

function page(index: number): void {
    emit('update:pagination', { ...props.pagination, pageIndex: index });
}

function size(value: unknown): void {
    emit('update:pagination', { pageIndex: 0, pageSize: Number(value) });
}
</script>

<template>
    <div
        class="grid w-full grid-cols-1 items-center gap-4 @2xl/datatable:grid-cols-2 @4xl/datatable:grid-cols-[minmax(0,1fr)_auto_auto]"
    >
        <div class="flex flex-col gap-1 text-sm text-muted-foreground">
            <p>
                {{
                    t('table.pagination.helper.summary', {
                        from: displayFrom,
                        to: displayTo,
                        total: rowCount,
                    })
                }}
            </p>
            <p
                v-if="selectedCount"
                aria-live="polite"
            >
                {{ t('table.helper.selected_count', { count: selectedCount }) }}
            </p>
        </div>
        <div
            class="flex items-center justify-between gap-2 @2xl/datatable:justify-end"
        >
            <span class="text-sm whitespace-nowrap text-muted-foreground">
                {{ t('table.pagination.label.rows_per_page') }}
            </span>
            <Select
                :model-value="String(pagination.pageSize)"
                @update:model-value="size"
            >
                <SelectTrigger class="w-20">
                    <SelectValue
                        :placeholder="t('table.pagination.label.rows_per_page')"
                    />
                </SelectTrigger>
                <SelectContent side="top">
                    <SelectGroup>
                        <SelectItem
                            v-for="option in pageSizeOptions"
                            :key="option"
                            :value="String(option)"
                        >
                            {{ option }}
                        </SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
        </div>
        <div
            class="flex min-w-0 items-center justify-between gap-2 @2xl/datatable:col-span-2 @2xl/datatable:justify-center @4xl/datatable:col-span-1 @4xl/datatable:justify-end"
        >
            <p class="text-sm font-medium whitespace-nowrap">
                {{
                    t('table.pagination.helper.page_status', {
                        current: currentPage,
                        total: pageCount,
                    })
                }}
            </p>
            <div class="flex items-center gap-1">
                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="currentPage <= 1"
                    :aria-label="t('table.pagination.button.first')"
                    @click="page(0)"
                >
                    <ChevronsLeft />
                </Button>
                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="currentPage <= 1"
                    :aria-label="t('table.pagination.button.previous')"
                    @click="page(pagination.pageIndex - 1)"
                >
                    <ChevronLeft />
                </Button>
                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="currentPage >= pageCount"
                    :aria-label="t('table.pagination.button.next')"
                    @click="page(pagination.pageIndex + 1)"
                >
                    <ChevronRight />
                </Button>
                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="currentPage >= pageCount"
                    :aria-label="t('table.pagination.button.last')"
                    @click="page(pageCount - 1)"
                >
                    <ChevronsRight />
                </Button>
            </div>
        </div>
    </div>
</template>
