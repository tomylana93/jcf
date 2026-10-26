<script setup lang="ts">
import { Head, setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';

import { update } from '@/actions/App/Http/Controllers/Settings/ModuleSettingsController';
import PageContainer from '@/components/layout/PageContainer.vue';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Switch } from '@/components/ui/switch';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/settings';
import { edit } from '@/routes/settings/modules';

const props = defineProps<{
    modules: Partial<
        Record<'website' | 'chat' | 'documentation' | 'backup', boolean>
    >;
}>();
const { t } = useTranslations();
const page = usePage();
const canManage = computed(() =>
    page.props.auth.permissions.includes('settings.manage'),
);
const form = useForm({ modules: { ...props.modules } });
watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [
            { title: t('breadcrumb.settings'), href: index() },
            { title: t('breadcrumb.modules'), href: edit() },
        ],
    }),
);
const submit = () =>
    form.submit(update(), {
        preserveScroll: true,
        onSuccess: () => {
            form.modules = { ...props.modules };
            form.defaults();
        },
    });
</script>

<template>
    <Head :title="t('settings.modules.title')" />

    <PageContainer
        :title="t('settings.modules.card.title')"
        :description="t('settings.modules.card.description')"
    >
        <form
            class="flex flex-col gap-6"
            @submit.prevent="submit"
        >
            <FieldGroup class="gap-0 divide-y">
                <Field
                    v-for="(_, module) in props.modules"
                    :key="module"
                    :data-invalid="!!form.errors[`modules.${module}`]"
                    :data-disabled="!canManage || form.processing"
                    class="py-4"
                    orientation="horizontal"
                >
                    <div class="flex-1">
                        <FieldLabel :for="`module-${module}`">
                            {{ t(`settings.modules.label.${module}`) }}
                        </FieldLabel>
                        <FieldDescription :id="`module-${module}-description`">
                            {{ t(`settings.modules.helper.${module}`) }}
                        </FieldDescription>
                        <FieldError
                            :errors="[form.errors[`modules.${module}`]]"
                        />
                    </div>
                    <Switch
                        :id="`module-${module}`"
                        v-model="form.modules[module]"
                        :disabled="!canManage || form.processing"
                        :aria-invalid="!!form.errors[`modules.${module}`]"
                        :aria-describedby="`module-${module}-description`"
                        :data-test="`module-${module}`"
                    />
                </Field>
            </FieldGroup>
            <FieldError :errors="[form.errors.modules]" />
            <div
                v-if="canManage"
                class="flex justify-end"
            >
                <Button
                    type="submit"
                    :disabled="form.processing"
                    data-test="save-module-settings"
                >
                    {{ t('common.button.save') }}
                </Button>
            </div>
        </form>
    </PageContainer>
</template>
