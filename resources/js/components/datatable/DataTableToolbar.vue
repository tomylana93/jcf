<script setup lang="ts" generic="TData extends RowData">
import { Columns3, Search } from '@lucide/vue';
import type { RowData, Table } from '@tanstack/vue-table';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { useTranslations } from '@/composables/useTranslations';
import type {
    DataTableAction,
    DataTableFilter as FilterDefinition,
} from '@/types';
import type { dataTableFeatures } from './dataTable';
import DataTableFilter from './DataTableFilter.vue';
import DataTableToolbarActions from './DataTableToolbarActions.vue';

const { t } = useTranslations();

const props = withDefaults(
    defineProps<{
        table: Table<typeof dataTableFeatures, TData>;
        search: string;
        searchPlaceholder: string;
        filters?: FilterDefinition[];
        filterValues?: Record<string, string>;
        actions?: DataTableAction[];
    }>(),
    { filters: () => [], filterValues: () => ({}), actions: () => [] },
);

const hideableColumns = computed(() =>
    props.table.getAllLeafColumns().filter((column) => column.getCanHide()),
);

const emit = defineEmits<{
    'update:search': [value: string];
    'update:filter': [key: string, value: string];
}>();
</script>

<template>
    <div class="@container/toolbar min-w-0">
        <div
            class="flex min-w-0 flex-col gap-3 @5xl/toolbar:flex-row @5xl/toolbar:items-center"
        >
            <div
                class="flex min-w-0 flex-col gap-2 @2xl/toolbar:flex-row @2xl/toolbar:items-center @5xl/toolbar:flex-1"
            >
                <InputGroup
                    class="h-9 w-full min-w-0 @2xl/toolbar:max-w-sm @2xl/toolbar:flex-1"
                >
                    <InputGroupAddon><Search /></InputGroupAddon>
                    <InputGroupInput
                        :model-value="search"
                        :placeholder="searchPlaceholder"
                        :aria-label="t('table.label.search')"
                        @update:model-value="
                            emit('update:search', String($event))
                        "
                    />
                </InputGroup>
                <div
                    class="grid min-w-0 grid-cols-1 gap-2 @sm/toolbar:grid-cols-2 @2xl/toolbar:flex @2xl/toolbar:flex-wrap @2xl/toolbar:items-center @5xl/toolbar:flex-nowrap"
                >
                    <DataTableFilter
                        v-for="filter in filters"
                        :key="filter.key"
                        :filter="filter"
                        :model-value="filterValues[filter.key] ?? ''"
                        @update:model-value="
                            emit('update:filter', filter.key, $event)
                        "
                    />
                </div>
            </div>
            <div
                class="flex flex-wrap items-center gap-2 border-t pt-3 @5xl/toolbar:shrink-0 @5xl/toolbar:flex-nowrap @5xl/toolbar:border-t-0 @5xl/toolbar:pt-0"
            >
                <DropdownMenu v-if="hideableColumns.length">
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" class="h-9">
                            <Columns3 data-icon="inline-start" />
                            {{ t('table.button.columns') }}
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuGroup>
                            <DropdownMenuCheckboxItem
                                v-for="column in hideableColumns"
                                :key="column.id"
                                :model-value="column.getIsVisible()"
                                @update:model-value="
                                    column.toggleVisibility($event === true)
                                "
                                @select.prevent
                            >
                                {{
                                    typeof column.columnDef.header === 'string'
                                        ? column.columnDef.header
                                        : column.id
                                }}
                            </DropdownMenuCheckboxItem>
                        </DropdownMenuGroup>
                    </DropdownMenuContent>
                </DropdownMenu>
                <slot />
                <DataTableToolbarActions
                    v-if="actions.length"
                    :actions="actions"
                    class="ml-auto @5xl/toolbar:flex-nowrap"
                />
            </div>
        </div>
    </div>
</template>
