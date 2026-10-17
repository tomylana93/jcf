<script setup lang="ts">
import { Head, setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed, watchEffect } from 'vue';

import { update } from '@/actions/App/Http/Controllers/Settings/AuthSettingsController';
import PageContainer from '@/components/layout/PageContainer.vue';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/settings';
import { edit } from '@/routes/settings/auth';
import type { AuthSettings, UpdateAuthSettingsForm } from '@/types';

const props = defineProps<{ settings: AuthSettings }>();

const { t } = useTranslations();

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('settings.manage'),
);

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: t('breadcrumb.settings'), href: index() },
            { title: t('breadcrumb.auth'), href: edit() },
        ],
    });
});

const form = useForm<UpdateAuthSettingsForm>({
    ...props.settings,
});

const submit = () => {
    form.submit(update(), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="t('settings.auth.title')" />

    <PageContainer
        :title="t('settings.auth.card.title')"
        :description="t('settings.auth.card.description')"
    >
        <form
            novalidate
            class="flex w-full flex-col gap-6"
            @submit.prevent="submit"
        >
            <FieldGroup>
                <Field
                    orientation="horizontal"
                    :data-invalid="!!form.errors.registration_enabled"
                >
                    <FieldContent>
                        <FieldLabel for="registration_enabled">
                            {{ t('settings.auth.label.registration_enabled') }}
                        </FieldLabel>
                        <FieldDescription>
                            {{ t('settings.auth.helper.registration_enabled') }}
                        </FieldDescription>
                        <FieldError
                            id="registration_enabled_error"
                            :errors="[form.errors.registration_enabled]"
                        />
                    </FieldContent>

                    <Switch
                        id="registration_enabled"
                        v-model="form.registration_enabled"
                        :disabled="!canManage"
                        :aria-invalid="!!form.errors.registration_enabled"
                        aria-describedby="registration_enabled_error"
                    />
                </Field>

                <Field
                    orientation="horizontal"
                    :data-invalid="!!form.errors.email_verification_required"
                >
                    <FieldContent>
                        <FieldLabel for="email_verification_required">
                            {{
                                t(
                                    'settings.auth.label.email_verification_required',
                                )
                            }}
                        </FieldLabel>
                        <FieldDescription>
                            {{
                                t(
                                    'settings.auth.helper.email_verification_required',
                                )
                            }}
                        </FieldDescription>
                        <FieldError
                            id="email_verification_required_error"
                            :errors="[form.errors.email_verification_required]"
                        />
                    </FieldContent>

                    <Switch
                        id="email_verification_required"
                        v-model="form.email_verification_required"
                        :disabled="!canManage"
                        :aria-invalid="
                            !!form.errors.email_verification_required
                        "
                        aria-describedby="email_verification_required_error"
                    />
                </Field>

                <Field
                    v-for="name in [
                        'password_min_length',
                        'login_max_attempts',
                        'login_lockout_minutes',
                    ] as const"
                    :key="name"
                    :data-invalid="!!form.errors[name]"
                >
                    <FieldLabel :for="name">
                        {{ t(`settings.auth.label.${name}`) }}
                    </FieldLabel>
                    <Input
                        :id="name"
                        v-model.number="form[name]"
                        :name="name"
                        type="number"
                        :readonly="!canManage"
                        :aria-invalid="!!form.errors[name]"
                        :aria-describedby="`${name}_error`"
                    />
                    <FieldError
                        :id="`${name}_error`"
                        :errors="[form.errors[name]]"
                    />
                </Field>
            </FieldGroup>

            <div
                v-if="canManage"
                class="flex flex-col gap-3 sm:items-end"
            >
                <Button
                    type="submit"
                    :disabled="form.processing"
                    data-test="save-auth-settings"
                >
                    <Save data-icon="inline-start" />
                    {{ t('common.button.save') }}
                </Button>
            </div>
        </form>
    </PageContainer>
</template>
