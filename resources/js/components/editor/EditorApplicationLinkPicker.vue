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
import { useTranslations } from '@/composables/useTranslations';
import type { EditorLinkOption } from '@/types';

const props = defineProps<{
    modelValue: string;
    options: EditorLinkOption[];
}>();

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const { t } = useTranslations();

const selected = computed(() =>
    props.options.find((option) => option.href === props.modelValue),
);
</script>

<template>
    <Combobox :model-value="selected" by="href">
        <ComboboxAnchor as-child>
            <ComboboxTrigger as-child>
                <Button
                    id="editor-application-link"
                    type="button"
                    variant="outline"
                    class="w-full justify-between"
                    data-test="editor-application-link"
                >
                    <span class="truncate">{{
                        selected?.label ??
                        t('editor.placeholder.application_page')
                    }}</span>
                    <ChevronsUpDown data-icon="inline-end" />
                </Button>
            </ComboboxTrigger>
        </ComboboxAnchor>
        <ComboboxList align="start" class="w-(--reka-combobox-trigger-width)">
            <ComboboxInput
                :placeholder="t('editor.placeholder.search_pages')"
                :aria-label="t('editor.label.search_pages')"
                data-test="editor-application-link-search"
            />
            <ComboboxEmpty>{{ t('editor.helper.no_pages') }}</ComboboxEmpty>
            <ComboboxGroup>
                <ComboboxItem
                    v-for="option in options"
                    :key="option.href"
                    :value="option"
                    @select="emit('update:modelValue', option.href)"
                >
                    {{ option.label }}
                    <ComboboxItemIndicator><Check /></ComboboxItemIndicator>
                </ComboboxItem>
            </ComboboxGroup>
        </ComboboxList>
    </Combobox>
</template>
