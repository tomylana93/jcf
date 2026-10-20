<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ImagePlus, Trash2 } from '@lucide/vue';
import { computed, ref, useId } from 'vue';

import ImageUploader from '@/components/shared/ImageUploader.vue';
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
const selectedFile = ref<File | null>(null);
const resetKey = ref(0);
const page = usePage();

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

function upload(): void {
    if (!selectedFile.value || props.disabled || props.uploadDisabled) return;
    emit('upload', selectedFile.value);
    selectedFile.value = null;
    resetKey.value++;
}
</script>

<template>
    <Field
        :data-image-field="title"
        :data-invalid="!!(props.errors?.media_id || props.errors?.alt)"
    >
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

        <Field v-if="props.value">
            <FieldLabel :for="altId">
                {{ t('website.label.image.alt') }}
            </FieldLabel>
            <Input
                :id="altId"
                :model-value="props.value.alt"
                type="text"
                :disabled="props.disabled"
                :aria-invalid="!!props.errors?.alt"
                :aria-describedby="
                    props.errors?.alt || props.errors?.media_id
                        ? `${uid}_alt_error`
                        : undefined
                "
                @update:model-value="
                    emit('update:value', {
                        media_id: props.value!.media_id,
                        alt: String($event),
                    })
                "
            />
            <FieldError
                :id="`${uid}_alt_error`"
                :errors="
                    [props.errors?.alt, props.errors?.media_id].filter(Boolean)
                "
            />
        </Field>
        <Dialog v-model:open="open">
            <DialogContent
                class="max-h-[calc(100dvh-2rem)] overflow-y-auto sm:max-w-lg"
            >
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
                        :data-media-id="item.id"
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
                <DialogFooter
                    class="flex-col gap-2 sm:flex-row sm:justify-between"
                >
                    <div class="flex w-full min-w-0 flex-col gap-3">
                        <ImageUploader
                            :src="currentImage?.url ?? ''"
                            :name="title"
                            kind="background"
                            :title="title"
                            :choose-label="t('website.button.upload_image')"
                            :disabled="
                                !!(props.disabled || props.uploadDisabled)
                            "
                            :invalid="!!page.props.errors.image"
                            :error="page.props.errors.image"
                            :reset-key="resetKey"
                            :allowed-file-types="[
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ]"
                            :max-file-size="5 * 1024 * 1024"
                            @selected="selectedFile = $event"
                            @removed="selectedFile = null"
                        />
                        <Button
                            v-if="selectedFile"
                            type="button"
                            :disabled="props.disabled || props.uploadDisabled"
                            data-test="upload_website_image"
                            @click="upload"
                        >
                            {{ t('website.button.upload_image') }}
                        </Button>
                        <FieldError :errors="[page.props.errors.image]" />
                    </div>
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
    </Field>
</template>
