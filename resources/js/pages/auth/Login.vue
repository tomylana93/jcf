<script setup lang="ts">
import { useForm, Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { Mail } from '@lucide/vue';
import { watchEffect } from 'vue';

import { store } from '@/actions/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import PasskeyVerify from '@/components/auth/passkeys/PasskeyVerify.vue';
import PasswordInput from '@/components/shared/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import { register } from '@/routes';
import { request } from '@/routes/password';
import type { LoginForm } from '@/types';

const { t } = useTranslations();

watchEffect(() => {
    setLayoutProps({
        title: t('auth.login.card.title'),
    });
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const form = useForm<LoginForm>({
    email: '',
    password: '',
});

const submit = () => {
    form.submit(store(), {
        onSuccess: () => form.reset('password'),
    });
};
</script>

<template>
    <div>
        <Head :title="t('auth.login.title')" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <PasskeyVerify
            :label="t('account.passkey.button.login')"
            :loading-label="t('account.passkey.status.verifying')"
            :separator="t('common.helper.or')"
        />

        <form novalidate @submit.prevent="submit" class="flex flex-col gap-6">
            <FieldGroup class="gap-6">
                <Field :data-invalid="!!form.errors.email">
                    <FieldLabel for="email">{{
                        t('user.label.email')
                    }}</FieldLabel>
                    <InputGroup>
                        <InputGroupInput
                            id="email"
                            name="email"
                            type="email"
                            inputmode="email"
                            autocapitalize="none"
                            spellcheck="false"
                            v-model="form.email"
                            v-focus
                            autocomplete="email"
                            :aria-label="t('user.label.email')"
                            :aria-invalid="!!form.errors.email"
                            :aria-describedby="
                                form.errors.email ? 'email-error' : undefined
                            "
                            :placeholder="t('user.placeholder.email')"
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
                    <FieldLabel for="password">{{
                        t('user.label.password')
                    }}</FieldLabel>
                    <PasswordInput
                        id="password"
                        name="password"
                        v-model="form.password"
                        autocomplete="current-password"
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
                    data-test="login-button"
                >
                    <Spinner v-if="form.processing" />
                    {{ t('auth.button.login') }}
                </Button>
            </FieldGroup>

            <Button
                v-if="canResetPassword"
                :as="Link"
                :href="request()"
                variant="ghost"
            >
                {{ t('auth.button.forgot_password') }}
            </Button>

            <Separator v-if="canRegister" />

            <Button
                v-if="canRegister"
                :as="Link"
                :href="register()"
                variant="secondary"
            >
                {{ t('auth.button.register') }}
            </Button>
        </form>
    </div>
</template>
