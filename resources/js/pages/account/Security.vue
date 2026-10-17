<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { watchEffect } from 'vue';

import SecurityController from '@/actions/App/Http/Controllers/Account/SecurityController';
import ManagePasskeys from '@/components/auth/passkeys/ManagePasskeys.vue';
import ManageTwoFactor from '@/components/auth/two-factor/ManageTwoFactor.vue';
import Heading from '@/components/shared/Heading.vue';
import PasswordInput from '@/components/shared/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup } from '@/components/ui/field';
import { useTranslations } from '@/composables/useTranslations';
import { edit } from '@/routes/security';
import type {
    ManagePasskeysProps,
    ManageTwoFactorProps,
    UpdatePasswordForm,
} from '@/types';

const { t } = useTranslations();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('breadcrumb.security'),
                href: edit(),
            },
        ],
    });
});

// oxfmt-ignore
type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

const props = defineProps<Props>();

const form = useForm<UpdatePasswordForm>({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.submit(SecurityController.update(), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => form.reset(),
    });
};
</script>

<template>
    <div class="flex flex-col gap-8">
        <Head :title="t('account.security.title')" />

        <h1 class="sr-only">{{ t('account.security.title') }}</h1>

        <div class="flex flex-col gap-6">
            <Heading
                variant="small"
                :title="t('account.security.card.title')"
                :description="t('account.security.card.description')"
            />

            <form
                novalidate
                @submit.prevent="submit"
                class="flex flex-col gap-6"
            >
                <FieldGroup>
                    <Field :data-invalid="!!form.errors.current_password">
                        <PasswordInput
                            id="current_password"
                            name="current_password"
                            v-model="form.current_password"
                            :aria-label="t('user.label.current_password')"
                            :aria-invalid="!!form.errors.current_password"
                            :aria-describedby="
                                form.errors.current_password
                                    ? 'current_password-error'
                                    : undefined
                            "
                            autocomplete="current-password"
                            :placeholder="
                                t('user.placeholder.current_password')
                            "
                        />
                        <FieldError
                            id="current_password-error"
                            :errors="[form.errors.current_password]"
                        />
                    </Field>

                    <Field :data-invalid="!!form.errors.password">
                        <PasswordInput
                            id="password"
                            name="password"
                            v-model="form.password"
                            :aria-label="t('user.label.new_password')"
                            :aria-invalid="!!form.errors.password"
                            :aria-describedby="
                                form.errors.password
                                    ? 'password-error'
                                    : undefined
                            "
                            autocomplete="new-password"
                            :placeholder="t('user.placeholder.new_password')"
                            :passwordrules="props.passwordRules"
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
                            :passwordrules="props.passwordRules"
                        />
                        <FieldError
                            id="password_confirmation-error"
                            :errors="[form.errors.password_confirmation]"
                        />
                    </Field>
                </FieldGroup>

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="form.processing"
                        data-test="update-password-button"
                    >
                        <Save data-icon="inline-start" />
                        {{ t('common.button.save') }}
                    </Button>
                </div>
            </form>
        </div>

        <ManageTwoFactor
            :canManageTwoFactor="canManageTwoFactor"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />

        <ManagePasskeys
            :canManagePasskeys="canManagePasskeys"
            :passkeys="passkeys"
        />
    </div>
</template>
