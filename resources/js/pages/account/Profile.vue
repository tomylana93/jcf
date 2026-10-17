<script setup lang="ts">
import { Head, setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { Mail, Save, UserRound } from '@lucide/vue';
import { computed, onMounted, ref, watchEffect } from 'vue';

import ProfileController from '@/actions/App/Http/Controllers/Account/ProfileController';
import DisableAccount from '@/components/account/DisableAccount.vue';
import ProfileAvatarForm from '@/components/account/ProfileAvatarForm.vue';
import Heading from '@/components/shared/Heading.vue';
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
import {
    NativeSelect,
    NativeSelectOption,
} from '@/components/ui/native-select';
import { Separator } from '@/components/ui/separator';
import { useTranslations } from '@/composables/useTranslations';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { AvatarSettings, UpdateProfileForm } from '@/types';

defineProps<{ avatarSettings: AvatarSettings; timezoneOptions: string[] }>();

const { t } = useTranslations();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('breadcrumb.profile'),
                href: edit(),
            },
        ],
    });
});

const page = usePage();

const user = computed(() => page.props.auth.user);

const avatarBusy = ref(false);

const form = useForm<UpdateProfileForm>({
    name: user.value.name,
    email: user.value.email,
    timezone: user.value.timezone ?? '',
});

const deviceTimezone = ref('');

onMounted(() => {
    deviceTimezone.value = Intl.DateTimeFormat().resolvedOptions().timeZone;
});

const submit = () => {
    form.submit(ProfileController.update(), {});
};
</script>

<template>
    <div>
        <Head :title="t('account.profile.title')" />

        <h1 class="sr-only">{{ t('account.profile.title') }}</h1>

        <div class="flex flex-col gap-6">
            <ProfileAvatarForm
                :settings="avatarSettings"
                :name="user.name"
                :disabled="form.processing"
                @busy="avatarBusy = $event"
            />
            <Separator />
            <Heading
                variant="small"
                :title="t('account.profile.card.title')"
                :description="t('account.profile.card.description')"
            />

            <form
                novalidate
                @submit.prevent="submit"
                class="flex flex-col gap-6"
                data-test="profile-form"
            >
                <FieldGroup>
                    <Field :data-invalid="Boolean(form.errors.name)">
                        <InputGroup>
                            <InputGroupInput
                                id="name"
                                name="name"
                                v-model="form.name"
                                :aria-label="t('user.label.name')"
                                :aria-describedby="
                                    form.errors.name ? 'name-error' : undefined
                                "
                                :aria-invalid="Boolean(form.errors.name)"
                                autocomplete="name"
                                :placeholder="t('user.placeholder.name')"
                            />

                            <InputGroupAddon>
                                <UserRound aria-hidden="true" />
                            </InputGroupAddon>
                        </InputGroup>

                        <FieldError
                            id="name-error"
                            :errors="[form.errors.name]"
                        />
                    </Field>

                    <Field :data-invalid="Boolean(form.errors.email)">
                        <InputGroup>
                            <InputGroupInput
                                id="email"
                                name="email"
                                v-model="form.email"
                                :aria-label="t('user.label.email')"
                                :aria-describedby="
                                    form.errors.email
                                        ? 'email-error'
                                        : undefined
                                "
                                :aria-invalid="Boolean(form.errors.email)"
                                autocomplete="username"
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
                </FieldGroup>

                <Field :data-invalid="Boolean(form.errors.timezone)">
                    <FieldLabel for="timezone">
                        {{ t('account.profile.label.timezone') }}
                    </FieldLabel>
                    <NativeSelect
                        id="timezone"
                        name="timezone"
                        v-model="form.timezone"
                        :aria-invalid="Boolean(form.errors.timezone)"
                        :aria-describedby="
                            form.errors.timezone ? 'timezone-error' : undefined
                        "
                        :disabled="form.processing"
                    >
                        <NativeSelectOption value="">
                            {{ t('account.profile.placeholder.timezone') }}
                        </NativeSelectOption>
                        <NativeSelectOption
                            v-for="timezone in timezoneOptions"
                            :key="timezone"
                            :value="timezone"
                        >
                            {{ timezone }}
                        </NativeSelectOption>
                    </NativeSelect>
                    <Button
                        type="button"
                        variant="outline"
                        class="self-start"
                        :disabled="form.processing || !deviceTimezone"
                        @click="form.timezone = deviceTimezone"
                    >
                        {{ t('account.profile.button.device_timezone') }}
                    </Button>
                    <FieldError
                        id="timezone-error"
                        :errors="[form.errors.timezone]"
                    />
                </Field>

                <div
                    v-if="page.props.mustVerifyEmail && !user.email_verified_at"
                >
                    <p class="-mt-4 text-sm text-muted-foreground">
                        {{ t('account.profile.helper.unverified') }}
                        <Link
                            :href="send()"
                            as="button"
                            class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                        >
                            {{ t('account.profile.link.resend') }}
                        </Link>
                    </p>

                    <div
                        v-if="page.props.status === 'verification-link-sent'"
                        class="mt-2 text-sm font-medium text-green-600"
                    >
                        {{ t('account.profile.status.verification_sent') }}
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="form.processing || avatarBusy"
                        data-test="update-profile-button"
                    >
                        <Save data-icon="inline-start" />
                        {{ t('common.button.save') }}
                    </Button>
                </div>
            </form>
        </div>

        <DisableAccount v-if="page.props.canDisableAccount" />
    </div>
</template>
