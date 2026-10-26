<script setup lang="ts">
import { Head, setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

import { update } from '@/actions/App/Http/Controllers/Website/WebsiteSettingsController';
import PageContainer from '@/components/layout/PageContainer.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import WebsiteLinkList from '@/components/website/WebsiteLinkList.vue';
import WebsiteSeoFields from '@/components/website/WebsiteSeoFields.vue';
import { useTranslations } from '@/composables/useTranslations';
import { index as websiteIndex } from '@/routes/website';
import { edit as settingsEdit } from '@/routes/website/settings';
import type { UpdateWebsiteSettingsForm, WebsiteSettingsProps } from '@/types';

const props = defineProps<WebsiteSettingsProps>();

const { t } = useTranslations();

const page = usePage();

const canConfigure = page.props.auth.permissions.includes('website.configure');

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: t('breadcrumb.website'), href: websiteIndex() },
            { title: t('breadcrumb.website_settings'), href: settingsEdit() },
        ],
    });
});

const form = useForm<UpdateWebsiteSettingsForm>({
    public_pages_enabled: props.settings.public_pages_enabled,
    identity: props.settings.identity,
    seo: {
        title: props.settings.seo.title,
        description: props.settings.seo.description,
        robots: props.settings.seo.robots,
    },
    contact: {
        email: props.settings.contact.email ?? '',
        phone: props.settings.contact.phone ?? '',
        address: props.settings.contact.address,
        hours: props.settings.contact.hours,
        location_url: props.settings.contact.location_url ?? '',
        social_links: props.settings.contact.social_links,
    },
    navigation: props.settings.navigation,
    footer: {
        text: props.settings.footer.text,
        links: props.settings.footer.links,
    },
}).withPrecognition(update());

/**
 * The SEO image picker is page-only; the shared defaults carry no image.
 */
function submit(): void {
    if (!canConfigure || form.processing) return;
    form.submit(update(), {
        preserveScroll: true,
        onSuccess: () => {
            form.defaults();
            form.reset();
        },
    });
}
</script>

<template>
    <Head :title="t('website.title.settings')" />

    <PageContainer
        :title="t('website.card.settings.title')"
        :description="t('website.card.settings.description')"
    >
        <form
            novalidate
            class="flex w-full flex-col gap-6"
            @submit.prevent="submit"
        >
            <Field orientation="horizontal">
                <FieldLabel for="public_pages_enabled">
                    {{ t('settings.general.label.public_pages_enabled') }}
                </FieldLabel>
                <Switch
                    id="public_pages_enabled"
                    v-model="form.public_pages_enabled"
                    :disabled="!canConfigure"
                />
                <FieldError :errors="[form.errors.public_pages_enabled]" />
            </Field>
            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ t('website.card.identity.title') }}
                    </CardTitle>
                    <CardDescription>
                        {{ t('website.card.identity.description') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Field :data-invalid="!!form.errors.identity">
                        <FieldLabel for="website_identity">
                            {{ t('website.label.identity') }}
                        </FieldLabel>
                        <Input
                            id="website_identity"
                            v-model="form.identity"
                            type="text"
                            :disabled="!canConfigure"
                            :aria-invalid="!!form.errors.identity"
                            data-test="website_setting_identity"
                        />
                        <FieldError
                            v-if="form.errors.identity"
                            :errors="[form.errors.identity] as string[]"
                        />
                    </Field>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ t('website.card.seo_defaults.title') }}
                    </CardTitle>
                    <CardDescription>
                        {{ t('website.card.seo_defaults.description') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <WebsiteSeoFields
                        :value="form.seo"
                        :disabled="!canConfigure"
                        prefix="seo"
                        :errors="form.errors"
                        @update:value="form.seo = $event"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('website.card.layout.title') }}</CardTitle>
                    <CardDescription>
                        {{ t('website.card.layout.description') }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="flex flex-col gap-6">
                    <WebsiteLinkList
                        :title="t('website.label.navigation.title')"
                        :value="form.navigation"
                        :disabled="!canConfigure"
                        prefix="navigation"
                        :errors="form.errors"
                        @update:value="form.navigation = $event"
                    />
                    <Field :data-invalid="!!form.errors['footer.text']">
                        <FieldLabel for="website_footer_text">
                            {{ t('website.label.footer.text') }}
                        </FieldLabel>
                        <Textarea
                            id="website_footer_text"
                            v-model="form.footer.text"
                            :rows="3"
                            :disabled="!canConfigure"
                            :aria-invalid="!!form.errors['footer.text']"
                            data-test="website_setting_footer_text"
                        />
                        <FieldError
                            v-if="form.errors['footer.text']"
                            :errors="[form.errors['footer.text']] as string[]"
                        />
                    </Field>
                    <WebsiteLinkList
                        :title="t('website.label.footer.links')"
                        :value="form.footer.links"
                        :disabled="!canConfigure"
                        prefix="footer.links"
                        :errors="form.errors"
                        @update:value="form.footer.links = $event"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>
                        {{ t('website.card.contact_info.title') }}
                    </CardTitle>
                    <CardDescription>
                        {{ t('website.card.contact_info.description') }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="flex flex-col gap-6">
                    <FieldGroup class="grid gap-4 sm:grid-cols-2">
                        <Field :data-invalid="!!form.errors['contact.email']">
                            <FieldLabel for="website_contact_email">
                                {{ t('website.label.contact.email') }}
                            </FieldLabel>
                            <Input
                                id="website_contact_email"
                                v-model="form.contact.email"
                                type="email"
                                :disabled="!canConfigure"
                                :aria-invalid="!!form.errors['contact.email']"
                                data-test="website_setting_contact_email"
                            />
                            <FieldError
                                v-if="form.errors['contact.email']"
                                :errors="
                                    [form.errors['contact.email']] as string[]
                                "
                            />
                        </Field>
                        <Field :data-invalid="!!form.errors['contact.phone']">
                            <FieldLabel for="website_contact_phone">
                                {{ t('website.label.contact.phone') }}
                            </FieldLabel>
                            <Input
                                id="website_contact_phone"
                                v-model="form.contact.phone"
                                type="tel"
                                :disabled="!canConfigure"
                                :aria-invalid="!!form.errors['contact.phone']"
                                data-test="website_setting_contact_phone"
                            />
                            <FieldError
                                v-if="form.errors['contact.phone']"
                                :errors="
                                    [form.errors['contact.phone']] as string[]
                                "
                            />
                        </Field>
                    </FieldGroup>
                    <Field :data-invalid="!!form.errors['contact.address']">
                        <FieldLabel for="website_contact_address">
                            {{ t('website.label.contact.address') }}
                        </FieldLabel>
                        <Textarea
                            id="website_contact_address"
                            v-model="form.contact.address"
                            :rows="2"
                            :disabled="!canConfigure"
                            :aria-invalid="!!form.errors['contact.address']"
                        />
                        <FieldError
                            v-if="form.errors['contact.address']"
                            :errors="
                                [form.errors['contact.address']] as string[]
                            "
                        />
                    </Field>
                    <FieldGroup class="grid gap-4 sm:grid-cols-2">
                        <Field :data-invalid="!!form.errors['contact.hours']">
                            <FieldLabel for="website_contact_hours">
                                {{ t('website.label.contact.hours') }}
                            </FieldLabel>
                            <Input
                                id="website_contact_hours"
                                v-model="form.contact.hours"
                                type="text"
                                :disabled="!canConfigure"
                                :aria-invalid="!!form.errors['contact.hours']"
                            />
                            <FieldError
                                v-if="form.errors['contact.hours']"
                                :errors="
                                    [form.errors['contact.hours']] as string[]
                                "
                            />
                        </Field>
                        <Field
                            :data-invalid="
                                !!form.errors['contact.location_url']
                            "
                        >
                            <FieldLabel for="website_contact_location">
                                {{ t('website.label.contact.location_url') }}
                            </FieldLabel>
                            <Input
                                id="website_contact_location"
                                v-model="form.contact.location_url"
                                type="text"
                                :disabled="!canConfigure"
                                :placeholder="t('website.placeholder.href')"
                                :aria-invalid="
                                    !!form.errors['contact.location_url']
                                "
                            />
                            <FieldError
                                v-if="form.errors['contact.location_url']"
                                :errors="
                                    [
                                        form.errors['contact.location_url'],
                                    ] as string[]
                                "
                            />
                        </Field>
                    </FieldGroup>
                    <WebsiteLinkList
                        :title="t('website.label.contact.social_links')"
                        :value="form.contact.social_links"
                        :disabled="!canConfigure"
                        prefix="contact.social_links"
                        :errors="form.errors"
                        @update:value="form.contact.social_links = $event"
                    />
                </CardContent>
            </Card>

            <div class="flex justify-end">
                <Button
                    type="submit"
                    size="sm"
                    :disabled="!canConfigure || form.processing"
                    data-test="save_website_settings"
                >
                    {{ t('common.button.save') }}
                </Button>
            </div>
        </form>
    </PageContainer>
</template>
