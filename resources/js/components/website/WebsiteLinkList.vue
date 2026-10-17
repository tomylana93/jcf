<script setup lang="ts">
import { Plus, X } from '@lucide/vue';

import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import WebsiteLinkField from '@/components/website/WebsiteLinkField.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { WebsiteLink } from '@/types';

const props = withDefaults(
    defineProps<{
        title: string;
        value: WebsiteLink[];
        disabled?: boolean;
        /** Error lookup by index: 'navigation.0.label' style keys, relative to the values array. */
        errors?: Record<string, string | undefined>;
        /** Rule keys prefix, e.g. 'settings.navigation' or 'settings.footer.links'; '' keeps plain indices. */
        prefix?: string;
        fieldLabel?: string;
        max?: number;
    }>(),
    { prefix: '', fieldLabel: undefined, max: 10 },
);

const emit = defineEmits<{
    'update:value': [value: WebsiteLink[]];
}>();

const { t } = useTranslations();

function add(): void {
    emit('update:value', [...props.value, { label: '', href: '' }]);
}

function remove(index: number): void {
    emit(
        'update:value',
        props.value.filter((_, at) => at !== index),
    );
}

function update(index: number, link: WebsiteLink): void {
    emit(
        'update:value',
        props.value.map((item, at) => (at === index ? link : item)),
    );
}

function errorFor(index: number, field: 'label' | 'href'): string | undefined {
    return props.errors?.[
        [props.prefix, String(index), field]
            .filter((part) => part !== '')
            .join('.')
    ];
}
</script>

<template>
    <FieldGroup>
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium">{{ title }}</span>
            <Button
                type="button"
                variant="outline"
                size="sm"
                :disabled="disabled || value.length >= props.max"
                data-test="add_website_link"
                @click="add"
            >
                <Plus class="size-4" />
                {{ t('website.button.add_link') }}
            </Button>
        </div>
        <div
            v-for="(link, index) in value"
            :key="index"
            class="flex items-start gap-2"
        >
            <div class="min-w-0 flex-1">
                <WebsiteLinkField
                    :title="
                        props.fieldLabel
                            ? `${props.fieldLabel} ${index + 1}`
                            : ''
                    "
                    :value="link"
                    :disabled="disabled"
                    :errors="{
                        label: errorFor(index, 'label'),
                        href: errorFor(index, 'href'),
                    }"
                    @update:value="update(index, $event)"
                />
            </div>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :disabled="disabled"
                :aria-label="t('website.button.remove')"
                data-test="remove_website_link"
                @click="remove(index)"
            >
                <X class="size-4" />
            </Button>
        </div>
        <p
            v-if="value.length === 0"
            class="text-sm text-muted-foreground"
        >
            {{ t('website.helper.no_links') }}
        </p>
    </FieldGroup>
</template>
