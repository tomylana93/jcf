<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

import { store } from '@/actions/Laravel/Fortify/Http/Controllers/ConfirmablePasswordController';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import PasskeyVerify from '@/components/auth/passkeys/PasskeyVerify.vue';
import PasswordInput from '@/components/shared/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import type { ConfirmPasswordForm } from '@/types';

const { t } = useTranslations();

watchEffect(() => {
    setLayoutProps({
        title: t('auth.confirm_password.card.title'),
    });
});

const form = useForm<ConfirmPasswordForm>({
    password: '',
});

const submit = () => {
    form.submit(store(), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <div>
        <Head :title="t('auth.confirm_password.title')" />

        <PasskeyVerify
            :routes="{
                options: confirmOptions(),
                submit: confirmStore(),
            }"
            :label="t('account.passkey.button.confirm')"
            :loading-label="t('account.passkey.status.verifying')"
            :separator="t('common.helper.or')"
        />

        <form
            novalidate
            @submit.prevent="submit"
        >
            <FieldGroup class="gap-6">
                <Field :data-invalid="!!form.errors.password">
                    <PasswordInput
                        id="password"
                        name="password"
                        v-model="form.password"
                        autocomplete="current-password"
                        autofocus
                        :aria-label="t('user.label.password')"
                        :aria-invalid="!!form.errors.password"
                        :aria-describedby="
                            form.errors.password ? 'password-error' : undefined
                        "
                        :placeholder="t('user.placeholder.password')"
                    />

                    <FieldError
                        id="password-error"
                        :errors="[form.errors.password]"
                    />
                </Field>

                <Button
                    type="submit"
                    :disabled="form.processing"
                    data-test="confirm-password-button"
                >
                    <Spinner v-if="form.processing" />
                    {{ t('auth.button.confirm_password') }}
                </Button>
            </FieldGroup>
        </form>
    </div>
</template>
