<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, shallowRef, watch } from 'vue';

import {
    discard,
    update,
    upload,
} from '@/actions/App/Http/Controllers/Account/ProfileAvatarController';
import Heading from '@/components/shared/Heading.vue';
import ImageUploader from '@/components/shared/ImageUploader.vue';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup } from '@/components/ui/field';
import { Progress } from '@/components/ui/progress';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import type { AvatarSettings, SaveAvatarForm, UploadAvatarForm } from '@/types';

const props = defineProps<{
    settings: AvatarSettings;
    name: string;
    disabled?: boolean;
}>();

const emit = defineEmits<{ busy: [value: boolean] }>();

const { t } = useTranslations();

const uploadForm = useForm<UploadAvatarForm>({ avatar: null });

const saveForm = useForm<SaveAvatarForm>({
    temporary_avatar: null,
    remove_avatar: false,
});

const resetKey = ref(0);

const queuedFile = shallowRef<File | null>(null);

const lastFile = shallowRef<File | null>(null);

let discardAfterUpload = false;

let disposed = false;

const busy = computed(
    () =>
        uploadForm.processing ||
        saveForm.processing ||
        queuedFile.value !== null,
);

const error = computed(
    () =>
        uploadForm.errors.avatar ||
        saveForm.errors.temporary_avatar ||
        saveForm.errors.remove_avatar,
);

const preview = computed(() =>
    saveForm.remove_avatar
        ? ''
        : (props.settings.temporary?.url ?? props.settings.current_url),
);

const uploadStatus = computed(() =>
    uploadForm.processing
        ? t(
              uploadForm.progress?.percentage === 100
                  ? 'account.avatar.status.processing'
                  : 'account.avatar.status.uploading',
          )
        : undefined,
);

watch(busy, (value) => emit('busy', value));

function uploadFile(file: File) {
    if (file.size > 2 * 1024 * 1024) {
        queuedFile.value = null;
        lastFile.value = null;
        uploadForm.setError('avatar', t('account.avatar.status.too_large'));
        return;
    }
    uploadForm.clearErrors();
    discardAfterUpload = false;
    lastFile.value = file;
    saveForm.remove_avatar = false;
    saveForm.clearErrors();
    if (uploadForm.processing) {
        queuedFile.value = file;
        return;
    }
    uploadForm.avatar = file;
    uploadForm.submit(upload(), {
        preserveScroll: true,
        onSuccess: () => uploadForm.reset(),
        onFinish: () => {
            if (disposed) {
                return;
            }
            if (discardAfterUpload) {
                discardAfterUpload = false;
                removeSelection();
                return;
            }
            const nextFile = queuedFile.value;
            queuedFile.value = null;
            if (nextFile) {
                uploadFile(nextFile);
            }
        },
    });
}

function resetSelection() {
    lastFile.value = null;
    resetKey.value++;
    uploadForm.reset();
    uploadForm.clearErrors();
    saveForm.reset();
}

function savePhoto() {
    saveForm.temporary_avatar = saveForm.remove_avatar
        ? null
        : (props.settings.temporary?.id ?? null);
    saveForm.submit(update(), {
        preserveScroll: true,
        onSuccess: resetSelection,
    });
}

function discardPhoto() {
    saveForm.temporary_avatar = props.settings.temporary?.id ?? null;
    saveForm.remove_avatar = false;
    saveForm.submit(discard(), {
        preserveScroll: true,
        onSuccess: resetSelection,
    });
}

function removeSelection() {
    queuedFile.value = null;
    lastFile.value = null;
    if (uploadForm.processing) {
        discardAfterUpload = true;
    } else if (props.settings.temporary) {
        discardPhoto();
    } else {
        resetSelection();
    }
}

onBeforeUnmount(() => {
    disposed = true;
    queuedFile.value = null;
    uploadForm.cancel();
    saveForm.cancel();
});
</script>

<template>
    <section
        class="flex flex-col gap-6"
        aria-labelledby="avatar-heading"
    >
        <Heading
            id="avatar-heading"
            variant="small"
            :title="t('account.avatar.title')"
        />
        <form
            novalidate
            class="flex flex-col gap-4"
            data-test="avatar-form"
            @submit.prevent="savePhoto"
        >
            <FieldGroup>
                <Field :data-invalid="Boolean(error)">
                    <ImageUploader
                        :src="preview"
                        :name="name"
                        :disabled="Boolean(disabled) || saveForm.processing"
                        :invalid="Boolean(error)"
                        :reset-key="resetKey"
                        :error="error"
                        :status="uploadStatus"
                        :progress="
                            uploadForm.processing
                                ? uploadForm.progress?.percentage
                                : undefined
                        "
                        @selected="uploadFile"
                        @removed="removeSelection"
                    />
                    <FieldError
                        v-if="error"
                        id="avatar-error"
                    >
                        {{ error }}
                    </FieldError>
                </Field>
            </FieldGroup>
            <div
                v-if="uploadForm.processing"
                class="flex flex-col gap-2"
                role="status"
            >
                <Progress
                    v-if="uploadForm.progress"
                    :model-value="uploadForm.progress.percentage"
                    :aria-label="t('account.avatar.status.uploading')"
                />
                <p class="text-sm text-muted-foreground">
                    {{
                        t(
                            uploadForm.progress?.percentage === 100
                                ? 'account.avatar.status.processing'
                                : 'account.avatar.status.uploading',
                        )
                    }}
                </p>
            </div>
            <p
                v-else-if="saveForm.remove_avatar"
                class="text-sm text-muted-foreground"
                role="status"
            >
                {{ t('account.avatar.status.removing') }}
            </p>
            <div class="flex flex-wrap gap-2">
                <Button
                    type="submit"
                    :disabled="
                        disabled ||
                        busy ||
                        Boolean(uploadForm.errors.avatar) ||
                        (!settings.temporary && !saveForm.remove_avatar)
                    "
                    data-test="save-avatar"
                >
                    <Spinner
                        v-if="saveForm.processing"
                        data-icon="inline-start"
                    />
                    <Save
                        v-else
                        data-icon="inline-start"
                    />
                    {{ t('account.avatar.button.save') }}
                </Button>
                <Button
                    v-if="settings.current_url"
                    type="button"
                    variant="outline"
                    :disabled="disabled || busy"
                    data-test="remove-avatar"
                    @click="saveForm.remove_avatar = !saveForm.remove_avatar"
                >
                    {{
                        t(
                            saveForm.remove_avatar
                                ? 'common.button.undo'
                                : 'account.avatar.button.remove',
                        )
                    }}
                </Button>
                <Button
                    v-if="settings.temporary"
                    type="button"
                    variant="ghost"
                    :disabled="disabled || busy"
                    data-test="discard-avatar"
                    @click="discardPhoto"
                >
                    {{ t('account.avatar.button.discard') }}
                </Button>
                <Button
                    v-if="uploadForm.errors.avatar && lastFile"
                    type="button"
                    variant="outline"
                    :disabled="disabled || busy"
                    @click="uploadFile(lastFile)"
                >
                    {{ t('account.avatar.button.retry') }}
                </Button>
            </div>
        </form>
    </section>
</template>
