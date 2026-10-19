<script setup lang="ts">
import { ImagePlus, Trash2 } from '@lucide/vue';
import { computed, ref, useId } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/composables/useTranslations';
import type { WebsiteImageRef, WebsiteMedia } from '@/types';

const props = defineProps<{
    title: string;
    value: WebsiteImageRef | null;
    media: WebsiteMedia[];
    disabled?: boolean;
    /** Uploads require a saved draft; disabled until then. */
    uploadDisabled?: boolean;
    errors?: { media_id?: string; alt?: string };
}>();

const emit = defineEmits<{
    'update:value': [value: WebsiteImageRef | null];
    upload: [file: File];
}>();

const { t } = useTranslations();

const open = ref(false);

const uid = useId();

const altId = `${uid}_alt`;

const currentImage = computed(
    () => props.media.find((item) => item.id === props.value?.media_id) ?? null,
);

function choose(media: WebsiteMedia): void {
    emit('update:value', { media_id: media.id, alt: props.value?.alt ?? '' });
    open.value = false;
}

function remove(): void {
    emit('update:value', null);
}

function upload(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
        emit('upload', file);
    }
    (event.target as HTMLInputElement).value = '';
}
</script>

<template>
    <Field :data-invalid="!!(props.errors?.media_id || props.errors?.alt)">
        <FieldLabel>{{ title }}</FieldLabel>
        <div
            v-if="currentImage"
            class="flex items-center gap-3"
        >
            <img
                :src="currentImage.url"
                :alt="props.value?.alt ?? ''"
                class="h-16 w-24 shrink-0 rounded-md border object-cover"
            />
            <div class="flex min-w-0 flex-col gap-1.5">
                <span class="truncate text-sm">{{ currentImage.name }}</span>
                <div class="flex gap-2">
                    <Button
                        v-if="!props.disabled"
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="open = true"
                    >
                        {{ t('website.button.use_image') }}
                    </Button>
                    <Button
                        v-if="!props.disabled"
                        type="button"
                        variant="ghost"
                        size="sm"
                        data-test="remove_website_image"
                        @click="remove"
                    >
                        <Trash2 class="size-4" />
                        {{ t('website.button.remove') }}
                    </Button>
                </div>
            </div>
        </div>
        <Button
            v-else-if="!props.disabled"
            type="button"
            variant="outline"
            size="sm"
            data-test="choose_website_image"
            @click="open = true"
        >
            <ImagePlus class="size-4" />
            {{ t('website.label.image.choose') }}
        </Button>

        <Field
            v-if="props.value"
            aria-labelledby="alt_field"
        >
            <FieldLabel :for="altId">
                {{ t('website.label.image.alt') }}
            </FieldLabel>
            <Input
                :id="altId"
                :model-value="props.value.alt"
                type="text"
                :disabled="props.disabled"
                :aria-invalid="!!props.errors?.alt"
                @update:model-value="
                    emit('update:value', {
                        media_id: props.value!.media_id,
                        alt: String($event),
                    })
                "
            />
            <FieldError
                id="alt_field"
                :errors="
                    [props.errors?.alt, props.errors?.media_id].filter(Boolean)
                "
            />
        </Field>
    </Field>

    <Dialog v-model:open="open">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    {{ t('website.helper.image_types') }}
                    {{ t('website.helper.alt_text') }}
                </DialogDescription>
            </DialogHeader>
            <div class="flex max-h-80 flex-col gap-2 overflow-y-auto">
                <button
                    v-for="item in props.media"
                    :key="item.id"
                    type="button"
                    class="flex items-center gap-3 rounded-lg border p-2 text-left transition-colors hover:bg-accent"
                    :class="
                        item.id === props.value?.media_id &&
                        'border-primary bg-accent'
                    "
                    :disabled="props.disabled"
                    @click="choose(item)"
                >
                    <img
                        :src="item.url"
                        alt=""
                        class="h-12 w-16 shrink-0 rounded-md border object-cover"
                    />
                    <span class="truncate text-sm">{{ item.name }}</span>
                </button>
                <p
                    v-if="props.media.length === 0"
                    class="px-2 text-sm text-muted-foreground"
                >
                    {{ t('website.helper.images_first_save') }}
                </p>
            </div>
            <DialogFooter class="flex-col gap-2 sm:flex-row sm:justify-between">
                <label
                    class="cursor-pointer"
                    :class="
                        (props.disabled || props.uploadDisabled) &&
                        'pointer-events-none opacity-50'
                    "
                >
                    <input
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="hidden"
                        :aria-label="t('website.button.upload_image')"
                        :disabled="props.disabled || props.uploadDisabled"
                        @change="upload"
                    />
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        as-child
                    >
                        <span data-test="upload_website_image">
                            <ImagePlus class="size-4" />
                            {{ t('website.button.upload_image') }}
                        </span>
                    </Button>
                </label>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="open = false"
                >
                    {{ t('common.button.cancel') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
