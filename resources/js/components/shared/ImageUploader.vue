<script setup lang="ts">
import { ImageIcon } from '@lucide/vue';
import Uppy, { type Meta, type UppyFile } from '@uppy/core';
import ImageEditor from '@uppy/image-editor';
import English from '@uppy/locales/lib/en_US';
import Indonesian from '@uppy/locales/lib/id_ID';
import Dashboard from '@uppy/vue/dashboard';
import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
    shallowRef,
    watch,
} from 'vue';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { FieldError } from '@/components/ui/field';
import { Progress } from '@/components/ui/progress';
import { useAppearance } from '@/composables/useAppearance';
import { useInitials } from '@/composables/useInitials';
import { useTranslations } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';

import '@uppy/core/css/style.css';
import '@uppy/dashboard/css/style.css';
import '@uppy/image-editor/css/style.css';

const props = withDefaults(
    defineProps<{
        src: string;
        name: string;
        disabled: boolean;
        invalid: boolean;
        resetKey: number;
        error?: string;
        status?: string;
        progress?: number;
        kind?: 'avatar' | 'logo' | 'favicon' | 'background';
        title?: string;
        chooseLabel?: string;
        doneLabel?: string;
    }>(),
    { kind: 'avatar' },
);

const emit = defineEmits<{ selected: [file: File]; removed: [] }>();

const { t, locale } = useTranslations();

const { getInitials } = useInitials();

const { resolvedAppearance } = useAppearance();

const open = ref(false);

const uppy = shallowRef<Uppy | null>(null);

let resetting = false;

const uppyLocale = computed(() =>
    locale.value === 'id' ? Indonesian : English,
);

function selectFile(file: UppyFile<Meta, Record<string, never>>) {
    if (file.data instanceof Blob) {
        emit(
            'selected',
            new File([file.data], file.name ?? props.kind, {
                type: file.data.type || file.type,
            }),
        );
    }
}

onMounted(() => {
    const instance = new Uppy({
        autoProceed: false,
        locale: uppyLocale.value,
        restrictions: { maxNumberOfFiles: 1 },
    }).use(ImageEditor, {
        ...(props.kind === 'logo' || props.kind === 'background'
            ? {}
            : { cropperOptions: { aspectRatio: 1 } }),
        actions: { cropWidescreen: false, cropWidescreenVertical: false },
    });
    instance.on('file-added', selectFile);
    instance.on('file-editor:complete', selectFile);
    instance.on('file-removed', () => {
        if (!resetting) {
            emit('removed');
        }
    });
    uppy.value = instance;
});

watch(uppyLocale, (value) => uppy.value?.setOptions({ locale: value }));

watch(
    () => props.resetKey,
    () => {
        resetting = true;
        uppy.value?.clear();
        resetting = false;
    },
);

onBeforeUnmount(() => {
    resetting = true;
    uppy.value?.destroy();
});
</script>

<template>
    <div
        :class="
            cn(
                'flex gap-4',
                kind === 'avatar' ? 'flex-wrap items-center' : 'flex-col',
            )
        "
    >
        <Avatar
            v-if="kind === 'avatar'"
            :key="src"
            class="size-20"
        >
            <AvatarImage
                v-if="src"
                :src="src"
                :alt="name"
            />
            <AvatarFallback>{{ getInitials(name) }}</AvatarFallback>
        </Avatar>
        <div
            v-else
            :class="
                cn(
                    'flex w-full items-center justify-center overflow-hidden rounded-lg border bg-muted/30',
                    kind === 'background' ? 'h-72' : 'h-36 p-4',
                )
            "
        >
            <img
                v-if="src"
                :src="src"
                :alt="name"
                :class="
                    cn(
                        'max-h-full max-w-full object-contain',
                        kind === 'favicon' ? 'size-14' : '',
                        kind === 'background' ? 'size-full object-cover' : '',
                    )
                "
            />
            <ImageIcon
                v-else
                class="size-10 text-muted-foreground"
                aria-hidden="true"
            />
        </div>
        <Dialog v-model:open="open">
            <Button
                type="button"
                variant="outline"
                :disabled="disabled"
                :aria-invalid="invalid"
                :aria-describedby="invalid ? `${kind}-error` : undefined"
                :data-test="`choose-${kind}`"
                @click="open = true"
            >
                {{ chooseLabel ?? t('account.avatar.button.choose') }}
            </Button>
            <DialogContent
                class="sm:max-w-2xl"
                :aria-describedby="undefined"
            >
                <DialogHeader>
                    <DialogTitle>
                        {{ title ?? t('account.avatar.title') }}
                    </DialogTitle>
                </DialogHeader>
                <Dashboard
                    v-if="uppy"
                    :uppy="uppy"
                    :props="{
                        height: 350,
                        width: '100%',
                        hideUploadButton: true,
                        proudlyDisplayPoweredByUppy: false,
                        theme: resolvedAppearance,
                        disabled,
                    }"
                />
                <div
                    v-if="status"
                    class="flex flex-col gap-2"
                    role="status"
                >
                    <Progress
                        v-if="progress !== undefined"
                        :model-value="progress"
                        :aria-label="status"
                    />
                    <p class="text-sm text-muted-foreground">{{ status }}</p>
                </div>
                <FieldError v-if="error">{{ error }}</FieldError>
                <DialogFooter>
                    <Button
                        type="button"
                        @click="open = false"
                    >
                        {{ doneLabel ?? t('common.button.done') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
