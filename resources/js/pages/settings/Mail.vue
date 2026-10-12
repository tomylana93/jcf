<script setup lang="ts">
import { Head, setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed, ref, watchEffect } from 'vue';

import {
    test,
    update,
} from '@/actions/App/Http/Controllers/Settings/MailSettingsController';
import PageContainer from '@/components/layout/PageContainer.vue';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/settings';
import { edit, preview } from '@/routes/settings/mail';
import type {
    MailSettings,
    SendMailSettingsTestForm,
    UpdateMailSettingsForm,
} from '@/types';

const props = defineProps<{ settings: MailSettings }>();

const { t } = useTranslations();

const page = usePage();
const canManage = computed(() =>
    page.props.auth.permissions.includes('settings.manage'),
);
const previewKey = ref(0);

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: t('breadcrumb.settings'), href: index() },
            { title: t('breadcrumb.mail'), href: edit() },
        ],
    });
});

const form = useForm<UpdateMailSettingsForm>({
    mailer: props.settings.mailer,
    smtp_host: props.settings.smtp_host,
    smtp_port: props.settings.smtp_port,
    smtp_scheme: props.settings.smtp_scheme,
    smtp_username: props.settings.smtp_username,
    smtp_password: '',
    from_address: props.settings.from_address,
    from_name: props.settings.from_name,
    show_logo: props.settings.show_logo,
    footer_text: props.settings.footer_text,
});
const testForm = useForm<SendMailSettingsTestForm>({
    test_email: '',
});

const submit = () => {
    form.submit(update(), {
        preserveScroll: true,
        onSuccess: () => {
            form.smtp_password = '';
            form.defaults();
            previewKey.value += 1;
        },
    });
};

const sendTest = () => {
    testForm.submit(test(), {
        preserveScroll: true,
    });
};
</script>

<template>
    <PageContainer
        :title="t('settings.mail.card.title')"
        :description="t('settings.mail.card.description')"
    >
        <Head :title="t('settings.mail.title')" />
        <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <form
                novalidate
                class="flex min-w-0 flex-col gap-8"
                @submit.prevent="submit"
            >
                <section class="flex flex-col gap-4">
                    <h2 class="text-lg font-semibold">
                        {{ t('settings.mail.section.delivery') }}
                    </h2>
                    <FieldGroup>
                        <Field :data-invalid="!!form.errors.mailer">
                            <FieldLabel for="mailer">{{
                                t('settings.mail.label.mailer')
                            }}</FieldLabel>
                            <select
                                id="mailer"
                                v-model="form.mailer"
                                name="mailer"
                                :disabled="!canManage"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="environment">
                                    {{ t('settings.mail.option.environment') }}
                                </option>
                                <option value="log">
                                    {{ t('settings.mail.option.log') }}
                                </option>
                                <option value="smtp">
                                    {{ t('settings.mail.option.smtp') }}
                                </option>
                            </select>
                            <FieldError :errors="[form.errors.mailer]" />
                            <FieldDescription v-if="form.mailer === 'log'">{{
                                t('settings.mail.hint.log')
                            }}</FieldDescription>
                        </Field>
                        <template v-if="form.mailer === 'smtp'">
                            <Field :data-invalid="!!form.errors.smtp_host">
                                <FieldLabel for="smtp_host">{{
                                    t('settings.mail.label.smtp_host')
                                }}</FieldLabel>
                                <Input
                                    id="smtp_host"
                                    v-model="form.smtp_host"
                                    name="smtp_host"
                                    :readonly="!canManage"
                                    :aria-invalid="!!form.errors.smtp_host"
                                />
                                <FieldError :errors="[form.errors.smtp_host]" />
                            </Field>
                            <Field :data-invalid="!!form.errors.smtp_port">
                                <FieldLabel for="smtp_port">{{
                                    t('settings.mail.label.smtp_port')
                                }}</FieldLabel>
                                <Input
                                    id="smtp_port"
                                    v-model.number="form.smtp_port"
                                    name="smtp_port"
                                    type="number"
                                    :readonly="!canManage"
                                    :aria-invalid="!!form.errors.smtp_port"
                                />
                                <FieldError :errors="[form.errors.smtp_port]" />
                            </Field>
                            <Field :data-invalid="!!form.errors.smtp_scheme">
                                <FieldLabel for="smtp_scheme">{{
                                    t('settings.mail.label.smtp_scheme')
                                }}</FieldLabel>
                                <select
                                    id="smtp_scheme"
                                    v-model="form.smtp_scheme"
                                    name="smtp_scheme"
                                    :disabled="!canManage"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="none">
                                        {{ t('settings.mail.option.none') }}
                                    </option>
                                    <option value="smtp">
                                        {{ t('settings.mail.option.starttls') }}
                                    </option>
                                    <option value="smtps">
                                        {{ t('settings.mail.option.smtps') }}
                                    </option>
                                </select>
                                <FieldError
                                    :errors="[form.errors.smtp_scheme]"
                                />
                            </Field>
                            <Field :data-invalid="!!form.errors.smtp_username">
                                <FieldLabel for="smtp_username">{{
                                    t('settings.mail.label.smtp_username')
                                }}</FieldLabel>
                                <Input
                                    id="smtp_username"
                                    v-model="form.smtp_username"
                                    name="smtp_username"
                                    :readonly="!canManage"
                                    :aria-invalid="!!form.errors.smtp_username"
                                />
                                <FieldError
                                    :errors="[form.errors.smtp_username]"
                                />
                            </Field>
                            <Field :data-invalid="!!form.errors.smtp_password">
                                <FieldLabel for="smtp_password">{{
                                    t('settings.mail.label.smtp_password')
                                }}</FieldLabel>
                                <Input
                                    id="smtp_password"
                                    type="password"
                                    v-model="form.smtp_password"
                                    name="smtp_password"
                                    autocomplete="new-password"
                                    :readonly="!canManage"
                                    :aria-invalid="!!form.errors.smtp_password"
                                />
                                <FieldDescription>{{
                                    props.settings.has_smtp_password
                                        ? t(
                                              'settings.mail.status.password_saved',
                                          )
                                        : t('settings.mail.hint.smtp_password')
                                }}</FieldDescription>
                                <FieldError
                                    :errors="[form.errors.smtp_password]"
                                />
                            </Field>
                        </template>
                    </FieldGroup>
                </section>

                <section class="flex flex-col gap-4">
                    <h2 class="text-lg font-semibold">
                        {{ t('settings.mail.section.sender') }}
                    </h2>
                    <FieldGroup>
                        <Field :data-invalid="!!form.errors.from_address">
                            <FieldLabel for="from_address">{{
                                t('settings.mail.label.from_address')
                            }}</FieldLabel>
                            <Input
                                id="from_address"
                                v-model="form.from_address"
                                name="from_address"
                                :readonly="!canManage"
                                :aria-invalid="!!form.errors.from_address"
                            />
                            <FieldError :errors="[form.errors.from_address]" />
                        </Field>
                        <Field :data-invalid="!!form.errors.from_name">
                            <FieldLabel for="from_name">{{
                                t('settings.mail.label.from_name')
                            }}</FieldLabel>
                            <Input
                                id="from_name"
                                v-model="form.from_name"
                                name="from_name"
                                :readonly="!canManage"
                                :aria-invalid="!!form.errors.from_name"
                            />
                            <FieldError :errors="[form.errors.from_name]" />
                        </Field>
                    </FieldGroup>
                </section>

                <section class="flex flex-col gap-4">
                    <h2 class="text-lg font-semibold">
                        {{ t('settings.mail.section.layout') }}
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        {{ t('settings.mail.hint.brand') }}
                    </p>
                    <FieldGroup>
                        <Field :data-invalid="!!form.errors.show_logo">
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <FieldLabel for="show_logo">{{
                                    t('settings.mail.label.show_logo')
                                }}</FieldLabel>
                                <Switch
                                    id="show_logo"
                                    v-model="form.show_logo"
                                    :disabled="!canManage"
                                />
                            </div>
                            <FieldError :errors="[form.errors.show_logo]" />
                        </Field>
                        <Field :data-invalid="!!form.errors.footer_text">
                            <FieldLabel for="footer_text">{{
                                t('settings.mail.label.footer_text')
                            }}</FieldLabel>
                            <Textarea
                                id="footer_text"
                                v-model="form.footer_text"
                                name="footer_text"
                                :readonly="!canManage"
                                :aria-invalid="!!form.errors.footer_text"
                            />
                            <FieldError :errors="[form.errors.footer_text]" />
                        </Field>
                    </FieldGroup>
                </section>

                <div v-if="canManage" class="flex justify-end">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        data-test="save-mail-settings"
                    >
                        <Save data-icon="inline-start" />
                        {{ t('common.button.save') }}
                    </Button>
                </div>
            </form>

            <section class="min-w-0 xl:sticky xl:top-6 xl:self-start">
                <h2 class="mb-4 text-lg font-semibold">
                    {{ t('settings.mail.section.preview') }}
                </h2>
                <iframe
                    id="mail-preview"
                    :key="previewKey"
                    :src="preview().url"
                    :title="t('settings.mail.section.preview')"
                    sandbox=""
                    class="h-[640px] w-full rounded-xl border bg-background"
                />
                <form
                    v-if="canManage"
                    novalidate
                    class="mt-6 flex flex-col gap-4"
                    @submit.prevent="sendTest"
                >
                    <h2 class="text-lg font-semibold">
                        {{ t('settings.mail.test.title') }}
                    </h2>
                    <FieldGroup class="gap-4">
                        <Field :data-invalid="!!testForm.errors.test_email">
                            <FieldLabel for="test_email">{{
                                t('settings.mail.test.email')
                            }}</FieldLabel>
                            <Input
                                id="test_email"
                                v-model="testForm.test_email"
                                name="test_email"
                                :aria-invalid="!!testForm.errors.test_email"
                            />
                            <FieldError
                                :errors="[testForm.errors.test_email]"
                            />
                        </Field>
                        <Button
                            type="submit"
                            :disabled="testForm.processing"
                            data-test="send-mail-test"
                        >
                            {{ t('settings.mail.test.send') }}
                        </Button>
                    </FieldGroup>
                </form>
            </section>
        </div>
    </PageContainer>
</template>
