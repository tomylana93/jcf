<script setup lang="ts">
import { Head, setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { computed, watchEffect } from 'vue';

import { update } from '@/actions/App/Http/Controllers/Settings/LayoutSettingsController';
import PageContainer from '@/components/layout/PageContainer.vue';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/settings';
import { edit } from '@/routes/settings/layout';
import type { LayoutSettings, UpdateLayoutSettingsForm } from '@/types';

type LayoutOption = { value: string; label: string; name: string };

const props = defineProps<{
    settings: LayoutSettings;
    appLayoutOptions: LayoutOption[];
    authLayoutOptions: LayoutOption[];
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
            { title: t('breadcrumb.layout'), href: edit() },
        ],
    });
});

const form = useForm<UpdateLayoutSettingsForm>({
    ...props.settings,
}).withPrecognition(update());

const submit = () => {
    form.submit(update(), {
        preserveScroll: true,
        onSuccess: () => {
            form.app_layout = props.settings.app_layout;
            form.auth_layout = props.settings.auth_layout;
            form.defaults();
        },
    });
};
</script>

<template>
    <Head :title="t('settings.layout.title')" />

    <PageContainer
        :title="t('settings.layout.card.title')"
        :description="t('settings.layout.card.description')"
    >
        <form
            novalidate
            class="flex w-full flex-col gap-6"
            @submit.prevent="submit"
        >
            <FieldGroup>
                <Field :data-invalid="!!form.errors.app_layout">
                    <FieldLabel>
                        {{ t('settings.layout.label.app_layout') }}
                    </FieldLabel>
                    <RadioGroup
                        v-model="form.app_layout"
                        :disabled="!canManage"
                        class="grid gap-3 sm:grid-cols-2"
                    >
                        <label
                            v-for="option in appLayoutOptions"
                            :key="option.value"
                            class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 has-data-[state=checked]:border-primary"
                        >
                            <RadioGroupItem
                                :id="`app-layout-${option.value}`"
                                :value="option.value"
                            />
                            <span class="flex flex-1 flex-col gap-3">
                                <span>{{ option.label }}</span>
                                <span
                                    class="flex h-20 overflow-hidden rounded border bg-muted/30"
                                    aria-hidden="true"
                                >
                                    <span
                                        v-if="option.value === 'sidebar'"
                                        class="w-1/4 border-r bg-muted"
                                    />
                                    <span class="flex flex-1 flex-col">
                                        <span
                                            v-if="option.value === 'header'"
                                            class="h-4 border-b bg-muted"
                                        />
                                        <span
                                            class="m-3 h-2 w-2/3 rounded bg-muted"
                                        />
                                    </span>
                                </span>
                            </span>
                        </label>
                    </RadioGroup>
                    <FieldError :errors="[form.errors.app_layout]" />
                </Field>
                <Field :data-invalid="!!form.errors.auth_layout">
                    <FieldLabel>
                        {{ t('settings.layout.label.auth_layout') }}
                    </FieldLabel>
                    <RadioGroup
                        v-model="form.auth_layout"
                        :disabled="!canManage"
                        class="grid gap-3 sm:grid-cols-3"
                    >
                        <label
                            v-for="option in authLayoutOptions"
                            :key="option.value"
                            class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 has-data-[state=checked]:border-primary"
                        >
                            <RadioGroupItem
                                :id="`auth-layout-${option.value}`"
                                :value="option.value"
                            />
                            <span class="flex flex-1 flex-col gap-3">
                                <span>{{ option.label }}</span>
                                <span
                                    class="flex h-20 items-center justify-center overflow-hidden rounded border bg-muted/30"
                                    aria-hidden="true"
                                >
                                    <span
                                        v-if="option.value === 'split'"
                                        class="h-full w-1/2 border-r bg-muted"
                                    />
                                    <span
                                        class="flex flex-1 items-center justify-center"
                                    >
                                        <span
                                            class="h-10 w-2/3 rounded"
                                            :class="
                                                option.value === 'card'
                                                    ? 'border bg-background shadow-sm'
                                                    : 'bg-muted'
                                            "
                                        />
                                    </span>
                                </span>
                            </span>
                        </label>
                    </RadioGroup>
                    <FieldError :errors="[form.errors.auth_layout]" />
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
                    data-test="save-layout-settings"
                >
                    <Save data-icon="inline-start" />
                    {{ t('common.button.save') }}
                </Button>
            </div>
        </form>
    </PageContainer>
</template>
