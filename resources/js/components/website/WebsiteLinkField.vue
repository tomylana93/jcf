<script setup lang="ts">
import { useId } from 'vue';

import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/composables/useTranslations';
import type { WebsiteLink } from '@/types';

const props = defineProps<{
    title: string;
    value: WebsiteLink;
    disabled?: boolean;
    errors?: { label?: string; href?: string };
}>();

const emit = defineEmits<{
    'update:value': [value: WebsiteLink];
}>();

const { t } = useTranslations();

const uid = useId();

const labelId = `${uid}_label`;

const hrefId = `${uid}_href`;
</script>

<template>
    <Field :data-invalid="!!(props.errors?.label || props.errors?.href)">
        <FieldLabel>{{ title }}</FieldLabel>
        <div
            class="grid gap-3 sm:grid-cols-[minmax(9rem,1fr)_minmax(12rem,1.5fr)]"
        >
            <div class="flex flex-col gap-1.5">
                <FieldLabel
                    :for="labelId"
                    class="text-xs text-muted-foreground"
                >
                    {{ t('website.label.navigation_link.label') }}
                </FieldLabel>
                <Input
                    :id="labelId"
                    :model-value="value.label"
                    type="text"
                    :disabled="disabled"
                    :aria-invalid="!!props.errors?.label"
                    @update:model-value="
                        emit('update:value', {
                            ...value,
                            label: String($event),
                        })
                    "
                />
                <FieldError
                    v-if="props.errors?.label"
                    :errors="[props.errors.label]"
                />
            </div>
            <div class="flex flex-col gap-1.5">
                <FieldLabel
                    :for="hrefId"
                    class="text-xs text-muted-foreground"
                >
                    {{ t('website.label.navigation_link.href') }}
                </FieldLabel>
                <Input
                    :id="hrefId"
                    :model-value="value.href"
                    type="text"
                    :disabled="disabled"
                    :placeholder="t('website.placeholder.href')"
                    :aria-invalid="!!props.errors?.href"
                    @update:model-value="
                        emit('update:value', { ...value, href: String($event) })
                    "
                />
                <FieldError
                    v-if="props.errors?.href"
                    :errors="[props.errors.href]"
                />
            </div>
        </div>
    </Field>
</template>
