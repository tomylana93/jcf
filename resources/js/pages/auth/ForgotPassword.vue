<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm } from '@inertiajs/vue3';
import { Mail } from '@lucide/vue';
import { watchEffect } from 'vue';

import { store as email } from '@/actions/Laravel/Fortify/Http/Controllers/PasswordResetLinkController';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup } from '@/components/ui/field';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import { login } from '@/routes';
import type { ForgotPasswordForm } from '@/types';

const { t } = useTranslations();

watchEffect(() => {
    setLayoutProps({
        title: t('auth.forgot_password.card.title'),
        description: t('auth.forgot_password.card.description'),
    });
});

defineProps<{
    status?: string;
}>();

const form = useForm<ForgotPasswordForm>({
    email: '',
});

const submit = () => {
    form.submit(email(), {});
};
</script>

<template>
    <div>
        <Head :title="t('auth.forgot_password.title')" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <div class="flex flex-col gap-6">
            <form
                novalidate
                @submit.prevent="submit"
                class="flex flex-col gap-6"
            >
                <FieldGroup class="gap-6">
                    <Field :data-invalid="!!form.errors.email">
                        <InputGroup>
                            <InputGroupInput
                                id="email"
                                name="email"
                                v-model="form.email"
                                autocomplete="off"
                                v-focus
                                :aria-label="t('user.label.email')"
                                :aria-invalid="!!form.errors.email"
                                :aria-describedby="
                                    form.errors.email
                                        ? 'email-error'
                                        : undefined
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

                    <Button
                        :disabled="form.processing"
                        data-test="email-password-reset-link-button"
                    >
                        <Spinner v-if="form.processing" />
                        {{ t('common.button.send') }}
                    </Button>
                </FieldGroup>
            </form>

            <Separator />

            <Button
                :as="Link"
                :href="login()"
                variant="secondary"
            >
                {{ t('auth.button.login') }}
            </Button>
        </div>
    </div>
</template>
