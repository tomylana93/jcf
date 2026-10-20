<script setup lang="ts">
import { useId } from 'vue';

import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import WebsiteImageField from '@/components/website/WebsiteImageField.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { WebsiteImageRef, WebsiteMedia, WebsiteSeoForm } from '@/types';

type Robots = WebsiteSeoForm['robots'];

type SeoValue = {
    title: string;
    description: string;
    robots: Robots;
};

const props = withDefaults(
    defineProps<{
        value: SeoValue;
        /** The image field applies to page SEO only, not to the global defaults. */
        image?: WebsiteImageRef | null;
        disabled?: boolean;
        media?: WebsiteMedia[];
        uploadDisabled?: boolean;
        errors?: Record<string, string | undefined>;
        prefix?: string;
        withImage?: boolean;
    }>(),
    { prefix: 'seo', withImage: false },
);

const emit = defineEmits<{
    'update:value': [value: SeoValue];
    'update:image': [value: WebsiteImageRef | null];
    upload: [file: File];
}>();

const { t } = useTranslations();

const uid = useId();

const robotsOptions: { value: Robots; label: string }[] = [
    { value: 'index,follow', label: 'index, follow' },
    { value: 'noindex,nofollow', label: 'noindex, nofollow' },
];

function errorFor(field: string): string | undefined {
    return props.errors?.[`${props.prefix}.${field}`];
}
</script>

<template>
    <FieldGroup>
        <Field :data-invalid="!!errorFor('title')">
            <FieldLabel :for="`${uid}_title`">
                {{ t('website.label.seo.title') }}
            </FieldLabel>
            <Input
                :id="`${uid}_title`"
                :model-value="props.value.title"
                type="text"
                :disabled="props.disabled"
                :aria-invalid="!!errorFor('title')"
                @update:model-value="
                    emit('update:value', {
                        ...props.value,
                        title: String($event),
                    })
                "
            />
            <FieldError
                v-if="errorFor('title')"
                :errors="[errorFor('title')!]"
            />
        </Field>
        <Field :data-invalid="!!errorFor('description')">
            <FieldLabel :for="`${uid}_description`">
                {{ t('website.label.seo.description') }}
            </FieldLabel>
            <Textarea
                :id="`${uid}_description`"
                :model-value="props.value.description"
                :rows="3"
                :disabled="props.disabled"
                :aria-invalid="!!errorFor('description')"
                @update:model-value="
                    emit('update:value', {
                        ...props.value,
                        description: String($event),
                    })
                "
            />
            <FieldError
                v-if="errorFor('description')"
                :errors="[errorFor('description')!]"
            />
        </Field>
        <Field :data-invalid="!!errorFor('robots')">
            <FieldLabel :for="`${uid}_robots`">
                {{ t('website.label.seo.robots') }}
            </FieldLabel>
            <Select
                :model-value="props.value.robots"
                :disabled="props.disabled"
                @update:model-value="
                    emit('update:value', {
                        ...props.value,
                        robots: $event as Robots,
                    })
                "
            >
                <SelectTrigger
                    :id="`${uid}_robots`"
                    :aria-invalid="!!errorFor('robots')"
                >
                    <SelectValue
                        :placeholder="t('website.placeholder.robots')"
                    />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        <SelectItem
                            v-for="option in robotsOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
            <FieldError
                v-if="errorFor('robots')"
                :errors="[errorFor('robots')!]"
            />
        </Field>
        <WebsiteImageField
            v-if="props.withImage"
            :title="t('website.label.seo.image')"
            :value="props.image ?? null"
            :media="props.media ?? []"
            :disabled="props.disabled"
            :upload-disabled="props.uploadDisabled"
            :errors="{
                media_id: errorFor('image.media_id'),
                alt: errorFor('image.alt'),
            }"
            @update:value="emit('update:image', $event)"
            @upload="emit('upload', $event)"
        />
    </FieldGroup>
</template>
