<script setup lang="ts">
import { Head, setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed, watchEffect } from 'vue';

import { update } from '@/actions/App/Http/Controllers/Settings/GeneralSettingsController';
import PageContainer from '@/components/layout/PageContainer.vue';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/settings';
import { edit } from '@/routes/settings/general';
import type { GeneralSettings, UpdateGeneralSettingsForm } from '@/types';

const props = defineProps<{
    settings: GeneralSettings;
}>();

const { t } = useTranslations();

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('settings.manage'),
);

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: t('breadcrumb.settings'), href: index() },
            { title: t('breadcrumb.general'), href: edit() },
        ],
    });
});

const form = useForm<UpdateGeneralSettingsForm>({
    ...props.settings,
    support_email: props.settings.support_email ?? '',
}).withPrecognition(update());

const submit = () => {
    form.submit(update(), {
        preserveScroll: true,
        onSuccess: () => {
            form.site_name = props.settings.site_name;
            form.site_description = props.settings.site_description;
            form.support_email = props.settings.support_email ?? '';
            form.defaults();
        },
    });
};
</script>

<template>
    <Head :title="t('settings.general.title')" />

    <PageContainer
        :title="t('settings.general.card.title')"
        :description="t('settings.general.card.description')"
    >
        <form
            novalidate
            class="flex w-full flex-col gap-6"
            @submit.prevent="submit"
        >
            <FieldGroup>
                <Field :data-invalid="!!form.errors.site_name">
                    <FieldLabel for="site_name">
                        {{ t('settings.general.label.site_name') }}
                    </FieldLabel>
                    <Input
                        id="site_name"
                        v-model="form.site_name"
                        name="site_name"
                        :readonly="!canManage"
                        :aria-invalid="!!form.errors.site_name"
                        aria-describedby="site_name_error"
                        @blur="canManage && form.validate('site_name')"
                    />
                    <FieldError
                        id="site_name_error"
                        :errors="[form.errors.site_name]"
                    />
                </Field>
                <Field :data-invalid="!!form.errors.site_description">
                    <FieldLabel for="site_description">
                        {{ t('settings.general.label.site_description') }}
                    </FieldLabel>
                    <Textarea
                        id="site_description"
                        v-model="form.site_description"
                        name="site_description"
                        :readonly="!canManage"
                        :aria-invalid="!!form.errors.site_description"
                        aria-describedby="site_description_error"
                        @blur="canManage && form.validate('site_description')"
                    />
                    <FieldError
                        id="site_description_error"
                        :errors="[form.errors.site_description]"
                    />
                </Field>
                <Field :data-invalid="!!form.errors.support_email">
                    <FieldLabel for="support_email">
                        {{ t('settings.general.label.support_email') }}
                    </FieldLabel>
                    <Input
                        id="support_email"
                        v-model="form.support_email"
                        name="support_email"
                        :readonly="!canManage"
                        :aria-invalid="!!form.errors.support_email"
                        aria-describedby="support_email_error"
                        @blur="canManage && form.validate('support_email')"
                    />
                    <FieldError
                        id="support_email_error"
                        :errors="[form.errors.support_email]"
                    />
                </Field>
            </FieldGroup>
            <p
                v-if="form.validating"
                role="status"
                class="text-sm text-muted-foreground"
            >
                {{ t('common.status.validating') }}
            </p>
            <div
                v-if="canManage"
                class="flex flex-col gap-3 sm:items-end"
            >
                <Button
                    type="submit"
                    :disabled="form.processing"
                    data-test="save-general-settings"
                >
                    <Save data-icon="inline-start" />
                    {{ t('common.button.save') }}
                </Button>
            </div>
        </form>
    </PageContainer>
</template>
