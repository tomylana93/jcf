<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { Mail } from '@lucide/vue';
import { watchEffect } from 'vue';

import { store as update } from '@/actions/Laravel/Fortify/Http/Controllers/NewPasswordController';
import PasswordInput from '@/components/shared/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup } from '@/components/ui/field';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import type { ResetPasswordForm } from '@/types';

const { t } = useTranslations();

watchEffect(() => {
    setLayoutProps({
        title: t('auth.reset_password.card.title'),
    });
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const form = useForm<ResetPasswordForm>({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.submit(update(), {
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div>
        <Head :title="t('auth.reset_password.title')" />

        <form
            novalidate
            @submit.prevent="submit"
        >
            <FieldGroup class="gap-6">
                <Field :data-invalid="!!form.errors.email">
                    <InputGroup>
                        <InputGroupInput
                            id="email"
                            name="email"
                            v-model="form.email"
                            :aria-label="t('user.label.email')"
                            :aria-invalid="!!form.errors.email"
                            :aria-describedby="
                                form.errors.email ? 'email-error' : undefined
                            "
                            autocomplete="email"
                            readonly
                        />

                        <InputGroupAddon>
                            <Mail aria-hidden="true" />
                        </InputGroupAddon>
                    </InputGroup>

                    <FieldError
                        id="email-error"
                        :errors="[form.errors.email]"
                    />
                </Field>

                <Field :data-invalid="!!form.errors.password">
                    <PasswordInput
                        id="password"
                        name="password"
                        v-model="form.password"
                        :aria-label="t('user.label.password')"
                        :aria-invalid="!!form.errors.password"
                        :aria-describedby="
                            form.errors.password ? 'password-error' : undefined
                        "
                        autocomplete="new-password"
                        autofocus
                        :placeholder="t('user.placeholder.password')"
                        :passwordrules="passwordRules"
                    />

                    <FieldError
                        id="password-error"
                        :errors="[form.errors.password]"
                    />
                </Field>

                <Field :data-invalid="!!form.errors.password_confirmation">
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        v-model="form.password_confirmation"
                        :aria-label="t('user.label.password_confirmation')"
                        :aria-invalid="!!form.errors.password_confirmation"
                        :aria-describedby="
                            form.errors.password_confirmation
                                ? 'password_confirmation-error'
                                : undefined
                        "
                        autocomplete="new-password"
                        :placeholder="
                            t('user.placeholder.password_confirmation')
                        "
                        :passwordrules="passwordRules"
                    />

                    <FieldError
                        id="password_confirmation-error"
                        :errors="[form.errors.password_confirmation]"
                    />
                </Field>

                <Button
                    type="submit"
                    :disabled="form.processing"
                    data-test="reset-password-button"
                >
                    <Spinner v-if="form.processing" />
                    {{ t('auth.button.reset_password') }}
                </Button>
            </FieldGroup>
        </form>
    </div>
</template>
