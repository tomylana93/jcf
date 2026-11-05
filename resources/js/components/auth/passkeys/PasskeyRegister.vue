<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { usePasskeyRegister } from '@laravel/passkeys/vue';
import { ref } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/composables/useTranslations';
import type { RegisterPasskeyForm } from '@/types';

const { t } = useTranslations();

const emit = defineEmits<{
    success: [];
}>();

const getDefaultPasskeyName = () => {
    const ua = navigator.userAgent;

    const browser = [
        { pattern: /Edg|Edge/, name: 'Edge' },
        { pattern: /OPR|Opera|OPiOS/, name: 'Opera' },
        { pattern: /Firefox|FxiOS/, name: 'Firefox' },
        { pattern: /Chrome|CriOS/, name: 'Chrome' },
        { pattern: /Safari/, name: 'Safari' },
    ].find(({ pattern }) => pattern.test(ua))?.name;

    const os = [
        { pattern: /iPhone/, name: 'iPhone' },
        { pattern: /iPad|Macintosh(?=.*Mobile)/, name: 'iPad' },
        { pattern: /Android/, name: 'Android' },
        { pattern: /Mac/, name: 'Mac' },
        { pattern: /Windows/, name: 'Windows' },
    ].find(({ pattern }) => pattern.test(ua))?.name;

    const parts = [browser, os].filter((part): part is string => Boolean(part));

    if (parts.length === 2) {
        return t('account.passkey.helper.device_name', {
            browser: parts[0],
            os: parts[1],
        });
    }

    return parts.join(' ') || '';
};

const form = useForm<RegisterPasskeyForm>({ name: getDefaultPasskeyName() });

const showForm = ref(false);

const { register, isLoading, error, isSupported } = usePasskeyRegister({
    onSuccess: () => {
        form.name = '';
        showForm.value = false;
        emit('success');
    },
});

const handleSubmit = async () => {
    if (!form.name.trim()) {
        return;
    }

    await register(form.name);
};

const handleCancel = () => {
    showForm.value = false;
    form.name = '';
};
</script>

<template>
    <div
        v-if="!isSupported"
        class="text-sm text-muted-foreground"
    >
        {{ t('account.passkey.helper.unsupported') }}
    </div>

    <Button
        v-else-if="!showForm"
        variant="outline"
        @click="showForm = true"
    >
        {{ t('account.passkey.button.add') }}
    </Button>

    <form
        v-else
        @submit.prevent="handleSubmit"
        class="space-y-4 rounded-lg border border-border bg-muted/50 p-4"
    >
        <Field :data-invalid="!!error">
            <FieldLabel for="passkey-name">
                {{ t('account.passkey.label.name') }}
            </FieldLabel>
            <Input
                id="passkey-name"
                type="text"
                v-model="form.name"
                :placeholder="t('account.passkey.placeholder.name')"
                class="border-foreground/20"
                :aria-invalid="!!error"
                aria-describedby="passkey-name-description"
                v-focus
            />
            <FieldDescription id="passkey-name-description">
                {{ t('account.passkey.helper.name') }}
            </FieldDescription>
            <FieldError :errors="[error ?? undefined]" />
        </Field>

        <div class="flex gap-2">
            <Button
                type="submit"
                :disabled="isLoading || !form.name.trim()"
            >
                {{
                    isLoading
                        ? t('account.passkey.status.registering')
                        : t('account.passkey.button.register')
                }}
            </Button>
            <Button
                type="button"
                variant="ghost"
                @click="handleCancel"
            >
                {{ t('common.button.cancel') }}
            </Button>
        </div>
    </form>
</template>
