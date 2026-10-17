<script setup lang="ts">
import { ArrowDown, ArrowUp, Plus, Trash2, X } from '@lucide/vue';
import { computed, ref } from 'vue';

import RichTextEditor from '@/components/editor/RichTextEditor.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import WebsiteImageField from '@/components/website/WebsiteImageField.vue';
import WebsiteLinkField from '@/components/website/WebsiteLinkField.vue';
import { useTranslations } from '@/composables/useTranslations';
import type {
    EditorLinkOption,
    WebsiteImageRef,
    WebsiteMedia,
    WebsiteSectionForm,
} from '@/types';

const props = defineProps<{
    section: WebsiteSectionForm;
    index: number;
    media: WebsiteMedia[];
    disabled?: boolean;
    uploadDisabled?: boolean;
    errors?: Record<string, string | undefined>;
    linkOptions?: EditorLinkOption[];
    isFirst: boolean;
    isLast: boolean;
}>();

const emit = defineEmits<{
    'update:section': [section: WebsiteSectionForm];
    move: [direction: -1 | 1];
    remove: [];
    upload: [file: File];
}>();

const { t } = useTranslations();

const typeLabels = {
    hero: 'website.label.section.type.hero',
    introduction: 'website.label.section.type.introduction',
    services: 'website.label.section.type.services',
    strengths: 'website.label.section.type.strengths',
    articles: 'website.label.section.type.articles',
    contact: 'website.label.section.type.contact',
} as const;

const typeLabel = computed(() => t(typeLabels[props.section.type]));

function error(field: string): string | undefined {
    return props.errors?.[`sections.${props.index}.${field}`];
}

function patch(partial: Partial<WebsiteSectionForm>): void {
    emit('update:section', {
        ...props.section,
        ...partial,
    } as WebsiteSectionForm);
}

function addItem(): void {
    if (
        props.section.type === 'services' ||
        props.section.type === 'strengths'
    ) {
        patch({
            items: [...props.section.items, { title: '', description: '' }],
        });
    }
}

function patchItem(
    index: number,
    partial: { title?: string; description?: string },
): void {
    if (
        props.section.type === 'services' ||
        props.section.type === 'strengths'
    ) {
        patch({
            items: props.section.items.map((item, at) =>
                at === index ? { ...item, ...partial } : item,
            ),
        });
    }
}

function removeItem(index: number): void {
    if (
        props.section.type === 'services' ||
        props.section.type === 'strengths'
    ) {
        patch({ items: props.section.items.filter((_, at) => at !== index) });
    }
}

const heroSection = computed(() =>
    props.section.type === 'hero' ? props.section : null,
);

const introductionSection = computed(() =>
    props.section.type === 'introduction' ? props.section : null,
);

const listingSection = computed(() =>
    props.section.type === 'articles' ? props.section : null,
);

const itemsSection = computed(() =>
    props.section.type === 'services' || props.section.type === 'strengths'
        ? props.section
        : null,
);

const contactSection = computed(() =>
    props.section.type === 'contact' ? props.section : null,
);

const ctaValue = computed(() =>
    props.section.type === 'contact'
        ? props.section.cta
        : (heroSection.value?.cta ?? null),
);

const sectionImage = computed<WebsiteImageRef | null>(
    () => heroSection.value?.image ?? null,
);

const richEditor = ref<InstanceType<typeof RichTextEditor> | null>(null);

defineExpose({
    /** Flushes the HTML source mode of the embedded editor before a save. */
    prepareSave: (): boolean => richEditor.value?.prepareSave() ?? true,
});
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-col">
                    <CardTitle>{{ typeLabel }}</CardTitle>
                    <CardDescription>
                        {{
                            section.visible
                                ? t('website.label.visible')
                                : t('website.helper.section.hidden')
                        }}
                    </CardDescription>
                </div>
                <div class="flex items-center gap-3">
                    <Field
                        orientation="horizontal"
                        :aria-label="t('website.label.visible')"
                    >
                        <Switch
                            :model-value="section.visible"
                            :disabled="disabled"
                            :aria-label="t('website.label.visible')"
                            @update:model-value="
                                patch({ visible: Boolean($event) })
                            "
                        />
                    </Field>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        :disabled="disabled || isFirst"
                        :aria-label="t('website.button.move_up')"
                        @click="emit('move', -1)"
                    >
                        <ArrowUp class="size-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        :disabled="disabled || isLast"
                        :aria-label="t('website.button.move_down')"
                        @click="emit('move', 1)"
                    >
                        <ArrowDown class="size-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        :disabled="disabled"
                        :aria-label="t('website.button.remove_section')"
                        data-test="remove_website_section"
                        @click="emit('remove')"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>
            </div>
        </CardHeader>
        <CardContent class="flex flex-col gap-6">
            <Field :data-invalid="!!error('title')">
                <FieldLabel :for="`section_${props.index}_title`">
                    {{ t('website.label.title') }}
                </FieldLabel>
                <Input
                    :id="`section_${props.index}_title`"
                    :model-value="section.title"
                    type="text"
                    :disabled="disabled"
                    :aria-invalid="!!error('title')"
                    @update:model-value="patch({ title: String($event) })"
                />
                <FieldError
                    v-if="error('title')"
                    :errors="[error('title')!]"
                />
            </Field>

            <template v-if="heroSection">
                <Field :data-invalid="!!error('description')">
                    <FieldLabel :for="`section_${props.index}_description`">
                        {{ t('website.label.section.hero.description') }}
                    </FieldLabel>
                    <Textarea
                        :id="`section_${props.index}_description`"
                        :model-value="heroSection.description"
                        :rows="3"
                        :disabled="disabled"
                        :aria-invalid="!!error('description')"
                        @update:model-value="
                            patch({ description: String($event) })
                        "
                    />
                    <FieldError
                        v-if="error('description')"
                        :errors="[error('description')!]"
                    />
                </Field>
                <WebsiteImageField
                    :title="t('website.label.section.hero.image_alt')"
                    :value="sectionImage"
                    :media="props.media"
                    :disabled="disabled"
                    :upload-disabled="props.uploadDisabled"
                    :errors="{
                        media_id: error('image.media_id'),
                        alt: error('image.alt'),
                    }"
                    @update:value="patch({ image: $event })"
                    @upload="emit('upload', $event)"
                />
                <WebsiteLinkField
                    v-if="ctaValue"
                    :title="t('website.label.section.hero.cta')"
                    :value="ctaValue"
                    :disabled="disabled"
                    :errors="{
                        label: error('cta.label'),
                        href: error('cta.href'),
                    }"
                    @update:value="patch({ cta: $event })"
                />
            </template>

            <template v-else-if="introductionSection">
                <RichTextEditor
                    ref="richEditor"
                    :model-value="introductionSection.body_json"
                    :disabled="disabled"
                    :error="error('body_json')"
                    :link-options="props.linkOptions"
                    @update:model-value="patch({ body_json: $event })"
                />
            </template>

            <template v-else-if="itemsSection">
                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium">
                            {{ t('website.label.sections') }}
                        </span>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="
                                disabled || itemsSection.items.length >= 20
                            "
                            data-test="add_website_section_item"
                            @click="addItem"
                        >
                            <Plus class="size-4" />
                            {{ t('website.button.add_item') }}
                        </Button>
                    </div>
                    <div
                        v-for="(item, itemIndex) in itemsSection.items"
                        :key="itemIndex"
                        class="rounded-lg border p-3"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-xs text-muted-foreground">
                                {{
                                    t('website.label.section.items.title', {
                                        number: itemIndex + 1,
                                    })
                                }}
                            </span>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                :disabled="disabled"
                                :aria-label="t('website.button.remove')"
                                @click="removeItem(itemIndex)"
                            >
                                <X class="size-4" />
                            </Button>
                        </div>
                        <div class="flex flex-col gap-3">
                            <Field
                                :data-invalid="
                                    !!error(`items.${itemIndex}.title`)
                                "
                            >
                                <FieldLabel
                                    :for="`section_${props.index}_item_${itemIndex}_title`"
                                    class="sr-only"
                                >
                                    {{ t('website.label.title') }}
                                </FieldLabel>
                                <Input
                                    :id="`section_${props.index}_item_${itemIndex}_title`"
                                    :model-value="item.title"
                                    type="text"
                                    :disabled="disabled"
                                    :placeholder="t('website.label.title')"
                                    :aria-invalid="
                                        !!error(`items.${itemIndex}.title`)
                                    "
                                    @update:model-value="
                                        patchItem(itemIndex, {
                                            title: String($event),
                                        })
                                    "
                                />
                                <FieldError
                                    v-if="error(`items.${itemIndex}.title`)"
                                    :errors="[
                                        error(`items.${itemIndex}.title`)!,
                                    ]"
                                />
                            </Field>
                            <Field
                                :data-invalid="
                                    !!error(`items.${itemIndex}.description`)
                                "
                            >
                                <FieldLabel
                                    :for="`section_${props.index}_item_${itemIndex}_description`"
                                    class="sr-only"
                                >
                                    {{
                                        t(
                                            'website.label.section.items.description',
                                        )
                                    }}
                                </FieldLabel>
                                <Textarea
                                    :id="`section_${props.index}_item_${itemIndex}_description`"
                                    :model-value="item.description"
                                    :rows="2"
                                    :disabled="disabled"
                                    :placeholder="
                                        t(
                                            'website.label.section.items.description',
                                        )
                                    "
                                    :aria-invalid="
                                        !!error(
                                            `items.${itemIndex}.description`,
                                        )
                                    "
                                    @update:model-value="
                                        patchItem(itemIndex, {
                                            description: String($event),
                                        })
                                    "
                                />
                                <FieldError
                                    v-if="
                                        error(`items.${itemIndex}.description`)
                                    "
                                    :errors="[
                                        error(
                                            `items.${itemIndex}.description`,
                                        )!,
                                    ]"
                                />
                            </Field>
                        </div>
                    </div>
                </div>
            </template>

            <template v-else-if="listingSection">
                <Field
                    orientation="horizontal"
                    :data-invalid="!!error('featured')"
                >
                    <FieldLabel for="website_section_featured">
                        {{ t('website.label.section.articles.featured') }}
                    </FieldLabel>
                    <Switch
                        id="website_section_featured"
                        :model-value="listingSection.featured"
                        :disabled="disabled"
                        @update:model-value="
                            patch({ featured: Boolean($event) })
                        "
                    />
                </Field>
                <Field :data-invalid="!!error('limit')">
                    <FieldLabel :for="`section_${props.index}_limit`">
                        {{ t('website.label.section.articles.limit') }}
                    </FieldLabel>
                    <Input
                        :id="`section_${props.index}_limit`"
                        :model-value="listingSection.limit"
                        type="number"
                        min="1"
                        max="12"
                        :disabled="disabled"
                        :aria-invalid="!!error('limit')"
                        @update:model-value="
                            patch({
                                limit: Math.max(
                                    1,
                                    Math.min(12, Number($event) || 1),
                                ),
                            })
                        "
                    />
                    <FieldError
                        v-if="error('limit')"
                        :errors="[error('limit')!]"
                    />
                </Field>
            </template>

            <template v-else-if="contactSection">
                <Field :data-invalid="!!error('description')">
                    <FieldLabel :for="`section_${props.index}_description`">
                        {{ t('website.label.section.contact.description') }}
                    </FieldLabel>
                    <Textarea
                        :id="`section_${props.index}_description`"
                        :model-value="contactSection.description"
                        :rows="3"
                        :disabled="disabled"
                        :aria-invalid="!!error('description')"
                        @update:model-value="
                            patch({ description: String($event) })
                        "
                    />
                    <FieldError
                        v-if="error('description')"
                        :errors="[error('description')!]"
                    />
                </Field>
                <WebsiteLinkField
                    :title="t('website.label.section.contact.cta')"
                    :value="contactSection.cta"
                    :disabled="disabled"
                    :errors="{
                        label: error('cta.label'),
                        href: error('cta.href'),
                    }"
                    @update:value="patch({ cta: $event })"
                />
            </template>
        </CardContent>
    </Card>
</template>
