<script setup lang="ts">
import { useForm, Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { Mail, UserRound } from '@lucide/vue';
import { watchEffect } from 'vue';

import { store } from '@/actions/Laravel/Fortify/Http/Controllers/RegisteredUserController';
import PasswordInput from '@/components/shared/PasswordInput.vue';
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
import type { RegisterForm } from '@/types';

const { t } = useTranslations();

watchEffect(() => {
    setLayoutProps({
        title: t('auth.register.card.title'),
    });
});

defineProps<{
    passwordRules: string;
}>();

const form = useForm<RegisterForm>({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.submit(store(), {
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div>
        <Head :title="t('auth.register.title')" />

        <form novalidate @submit.prevent="submit" class="flex flex-col gap-6">
            <FieldGroup class="gap-6">
                <Field :data-invalid="!!form.errors.name">
                    <InputGroup>
                        <InputGroupInput
                            id="name"
                            name="name"
                            v-model="form.name"
                            v-focus
                            :aria-label="t('user.label.name')"
                            :aria-invalid="!!form.errors.name"
                            :aria-describedby="
                                form.errors.name ? 'name-error' : undefined
                            "
                            autocomplete="name"
                            :placeholder="t('user.placeholder.name')"
                        />

                        <InputGroupAddon>
                            <UserRound aria-hidden="true" />
                        </InputGroupAddon>
                    </InputGroup>

                    <FieldError id="name-error" :errors="[form.errors.name]" />
                </Field>

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
                    data-test="register-user-button"
                >
                    <Spinner v-if="form.processing" />
                    {{ t('auth.button.register') }}
                </Button>
            </FieldGroup>

            <Separator />

            <Button :as="Link" :href="login()" variant="secondary">
                {{ t('auth.button.login') }}
            </Button>
        </form>
    </div>
</template>
