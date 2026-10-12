<script setup lang="ts">
import { Check, ChevronsUpDown } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxTrigger,
    ComboboxList,
    ComboboxInput,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxItem,
    ComboboxItemIndicator,
} from '@/components/ui/combobox';
import {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectGroup,
    SelectItem,
} from '@/components/ui/select';
import { useTranslations } from '@/composables/useTranslations';
import type { DataTableFilter } from '@/types';

const props = defineProps<{ filter: DataTableFilter; modelValue: string }>();

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const { t } = useTranslations();

const options = computed(() => [
    { value: '', label: t('table.placeholder.all') },
    ...props.filter.options,
]);

const selected = computed(() =>
    options.value.find((option) => option.value === props.modelValue),
);
function updateCombobox(value: unknown): void {
    const option = options.value.find((option) => option === value);
    emit('update:modelValue', option?.value ?? '');
}
</script>

<template>
    <Combobox
        v-if="filter.type === 'combobox'"
        :model-value="selected"
        by="value"
        @update:model-value="updateCombobox"
    >
        <ComboboxAnchor as-child>
            <ComboboxTrigger as-child>
                <Button
                    variant="outline"
                    class="h-9 w-full min-w-0 justify-between @2xl/toolbar:w-48"
                    :aria-label="filter.label"
                >
                    <span class="truncate"
                        >{{ filter.label }}:
                        {{
                            selected?.label ?? t('table.placeholder.all')
                        }}</span
                    >
                    <ChevronsUpDown data-icon="inline-end" />
                </Button>
            </ComboboxTrigger>
        </ComboboxAnchor>
        <ComboboxList>
            <ComboboxInput
                :placeholder="t('table.placeholder.search')"
                :aria-label="filter.label"
            />
            <ComboboxEmpty>{{ t('table.helper.empty') }}</ComboboxEmpty>
            <ComboboxGroup>
                <ComboboxItem
                    v-for="option in options"
                    :key="option.value"
                    :value="option"
                >
                    {{ option.label }}
                    <ComboboxItemIndicator><Check /></ComboboxItemIndicator>
                </ComboboxItem>
            </ComboboxGroup>
        </ComboboxList>
    </Combobox>
    <Select
        v-else
        :model-value="modelValue || '__all'"
        @update:model-value="
            emit('update:modelValue', $event === '__all' ? '' : String($event))
        "
    >
        <SelectTrigger
            class="h-9 w-full min-w-0 @2xl/toolbar:w-48"
            :aria-label="filter.label"
        >
            <SelectValue
                >{{ filter.label }}:
                {{ selected?.label ?? t('table.placeholder.all') }}</SelectValue
            >
        </SelectTrigger>
        <SelectContent>
            <SelectGroup>
                <SelectItem
                    v-for="option in options"
                    :key="option.value"
                    :value="option.value || '__all'"
                    >{{ option.label }}</SelectItem
                >
            </SelectGroup>
        </SelectContent>
    </Select>
</template>
