<script setup lang="ts">
import { Head, setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import { Save, ImageIcon, Palette, Trash2, Undo2 } from '@lucide/vue';
import { computed, onBeforeUnmount, reactive, watchEffect } from 'vue';

import { update } from '@/actions/App/Http/Controllers/Settings/BrandSettingsController';
import PageContainer from '@/components/layout/PageContainer.vue';
import ImageUploader from '@/components/shared/ImageUploader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Progress } from '@/components/ui/progress';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Spinner } from '@/components/ui/spinner';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useAppearance } from '@/composables/useAppearance';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/settings';
import { edit } from '@/routes/settings/brand';
import type {
    BrandSettings,
    ColorPreset,
    LogoTypeOption,
    UpdateBrandSettingsForm,
} from '@/types';

const props = defineProps<{
    settings: BrandSettings;
    colorPresets: ColorPreset[];
    logoTypeOptions: LogoTypeOption[];
}>();

const { t } = useTranslations();
const { resolvedAppearance } = useAppearance();

const page = usePage();
const canManage = computed(() =>
    page.props.auth.permissions.includes('settings.manage'),
);
type ImageField = 'logo' | 'logo_text' | 'favicon' | 'auth_background';
const previews = reactive<Record<ImageField, string | null>>({
    logo: null,
    logo_text: null,
    favicon: null,
    auth_background: null,
});
const resetKeys = reactive<Record<ImageField, number>>({
    logo: 0,
    logo_text: 0,
    favicon: 0,
    auth_background: 0,
});

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: t('breadcrumb.settings'), href: index() },
            { title: t('breadcrumb.brand'), href: edit() },
        ],
    });
});

const form = useForm<UpdateBrandSettingsForm>({
    _method: 'patch',
    logo_type: props.settings.logo_type,
    color: props.settings.color,
    logo: null,
    logo_text: null,
    favicon: null,
    auth_background: null,
    remove_logo: false,
    remove_logo_text: false,
    remove_favicon: false,
    remove_auth_background: false,
});

function clearPreview(field: ImageField) {
    if (previews[field]) {
        URL.revokeObjectURL(previews[field]);
        previews[field] = null;
    }
}

function selectImage(field: ImageField, file: File) {
    clearPreview(field);
    form[field] = file;
    form[`remove_${field}`] = false;
    previews[field] = URL.createObjectURL(file);
}

function clearImage(field: ImageField) {
    clearPreview(field);
    form[field] = null;
}

function toggleRemoval(field: ImageField) {
    const removeField = `remove_${field}` as const;
    form[removeField] = !form[removeField];
    if (form[removeField]) {
        clearImage(field);
        resetKeys[field]++;
    }
}

function imageSource(field: ImageField): string {
    const defaultImage = {
        logo:
            resolvedAppearance.value === 'dark'
                ? '/logo-wordmark-dark.svg'
                : '/logo-wordmark.svg',
        logo_text:
            resolvedAppearance.value === 'dark'
                ? '/logo-dark.svg'
                : '/logo.svg',
        favicon: '/favicon.svg',
        auth_background: '/images/auth-background.webp',
    }[field];
    if (form[`remove_${field}`]) return defaultImage;
    return previews[field] ?? props.settings[`${field}_url`] ?? defaultImage;
}

function imageStatus(field: ImageField): string {
    if (form[`remove_${field}`]) return t('settings.brand.status.removing');
    if (form[field]) return t('settings.brand.status.selected');
    if (props.settings[`${field}_url`])
        return t('settings.brand.status.current');
    return t('settings.brand.status.default');
}

function resetSelections() {
    for (const field of [
        'logo',
        'logo_text',
        'favicon',
        'auth_background',
    ] as const) {
        clearImage(field);
        resetKeys[field]++;
    }
    form.reset(
        'logo',
        'logo_text',
        'favicon',
        'auth_background',
        'remove_logo',
        'remove_logo_text',
        'remove_favicon',
        'remove_auth_background',
    );
    form.color = props.settings.color;
    form.logo_type = props.settings.logo_type;
    form.defaults();
}

onBeforeUnmount(() => {
    clearPreview('logo');
    clearPreview('logo_text');
    clearPreview('favicon');
    clearPreview('auth_background');
});

function submit() {
    form.post(update().url, {
        preserveScroll: true,
        onSuccess: resetSelections,
    });
}
</script>

<template>
    <PageContainer
        :title="t('settings.brand.card.title')"
        :description="t('settings.brand.card.description')"
    >
        <Head :title="t('settings.brand.title')" />
        <form
            novalidate
            class="flex w-full flex-col gap-6"
            @submit.prevent="submit"
        >
            <Field :data-invalid="!!form.errors.logo_type">
                <FieldLabel>{{
                    t('settings.brand.label.logo_type')
                }}</FieldLabel>
                <RadioGroup
                    v-model="form.logo_type"
                    :disabled="!canManage || form.processing"
                    class="grid gap-3 sm:grid-cols-2"
                >
                    <label
                        v-for="option in logoTypeOptions"
                        :key="option.value"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 has-data-[state=checked]:border-primary"
                    >
                        <RadioGroupItem
                            :id="`logo-type-${option.value}`"
                            :value="option.value"
                        />
                        <span>{{ option.label }}</span>
                    </label>
                </RadioGroup>
                <FieldError v-if="form.errors.logo_type">{{
                    form.errors.logo_type
                }}</FieldError>
            </Field>
            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <ImageIcon
                            class="size-5 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <CardTitle>{{
                            t('settings.brand.label.logo')
                        }}</CardTitle>
                        <CardDescription>{{
                            t('settings.brand.hint.logo')
                        }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <FieldGroup>
                            <Field :data-invalid="!!form.errors.logo">
                                <ImageUploader
                                    :src="imageSource('logo')"
                                    :name="t('settings.brand.label.logo')"
                                    kind="logo"
                                    :title="t('settings.brand.label.logo')"
                                    :choose-label="
                                        t('settings.brand.button.choose_logo')
                                    "
                                    :done-label="t('common.button.done')"
                                    :disabled="!canManage || form.processing"
                                    :invalid="!!form.errors.logo"
                                    :reset-key="resetKeys.logo"
                                    :error="form.errors.logo"
                                    @selected="selectImage('logo', $event)"
                                    @removed="clearImage('logo')"
                                />
                                <FieldError
                                    v-if="form.errors.logo"
                                    id="logo-error"
                                    >{{ form.errors.logo }}</FieldError
                                >
                            </Field>
                        </FieldGroup>
                    </CardContent>
                    <CardFooter class="justify-between gap-2 border-t">
                        <Badge variant="secondary">{{
                            imageStatus('logo')
                        }}</Badge>
                        <Button
                            v-if="canManage && settings.logo_url"
                            type="button"
                            variant="ghost"
                            size="sm"
                            :disabled="form.processing"
                            @click="toggleRemoval('logo')"
                        >
                            <Undo2
                                v-if="form.remove_logo"
                                data-icon="inline-start"
                            />
                            <Trash2 v-else data-icon="inline-start" />
                            {{
                                t(
                                    form.remove_logo
                                        ? 'common.button.undo'
                                        : 'settings.brand.button.remove_logo',
                                )
                            }}
                        </Button>
                    </CardFooter>
                </Card>

                <Card>
                    <CardHeader>
                        <ImageIcon
                            class="size-5 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <CardTitle>{{
                            t('settings.brand.label.logo_text')
                        }}</CardTitle>
                        <CardDescription>{{
                            t('settings.brand.hint.logo_text')
                        }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <FieldGroup>
                            <Field :data-invalid="!!form.errors.logo_text">
                                <ImageUploader
                                    :src="imageSource('logo_text')"
                                    :name="t('settings.brand.label.logo_text')"
                                    kind="logo"
                                    :title="t('settings.brand.label.logo_text')"
                                    :choose-label="
                                        t(
                                            'settings.brand.button.choose_logo_text',
                                        )
                                    "
                                    :done-label="t('common.button.done')"
                                    :disabled="!canManage || form.processing"
                                    :invalid="!!form.errors.logo_text"
                                    :reset-key="resetKeys.logo_text"
                                    :error="form.errors.logo_text"
                                    @selected="selectImage('logo_text', $event)"
                                    @removed="clearImage('logo_text')"
                                />
                                <FieldError
                                    v-if="form.errors.logo_text"
                                    id="logo_text-error"
                                    >{{ form.errors.logo_text }}</FieldError
                                >
                            </Field>
                        </FieldGroup>
                    </CardContent>
                    <CardFooter class="justify-between gap-2 border-t">
                        <Badge variant="secondary">{{
                            imageStatus('logo_text')
                        }}</Badge>
                        <Button
                            v-if="canManage && settings.logo_text_url"
                            type="button"
                            variant="ghost"
                            size="sm"
                            :disabled="form.processing"
                            @click="toggleRemoval('logo_text')"
                        >
                            <Undo2
                                v-if="form.remove_logo_text"
                                data-icon="inline-start"
                            />
                            <Trash2 v-else data-icon="inline-start" />
                            {{
                                t(
                                    form.remove_logo_text
                                        ? 'common.button.undo'
                                        : 'settings.brand.button.remove_logo_text',
                                )
                            }}
                        </Button>
                    </CardFooter>
                </Card>

                <Card>
                    <CardHeader>
                        <ImageIcon
                            class="size-5 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <CardTitle>{{
                            t('settings.brand.label.favicon')
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <FieldGroup>
                            <Field :data-invalid="!!form.errors.favicon">
                                <ImageUploader
                                    :src="imageSource('favicon')"
                                    :name="t('settings.brand.label.favicon')"
                                    kind="favicon"
                                    :title="t('settings.brand.label.favicon')"
                                    :choose-label="
                                        t(
                                            'settings.brand.button.choose_favicon',
                                        )
                                    "
                                    :done-label="t('common.button.done')"
                                    :disabled="!canManage || form.processing"
                                    :invalid="!!form.errors.favicon"
                                    :reset-key="resetKeys.favicon"
                                    :error="form.errors.favicon"
                                    @selected="selectImage('favicon', $event)"
                                    @removed="clearImage('favicon')"
                                />
                                <FieldError
                                    v-if="form.errors.favicon"
                                    id="favicon-error"
                                    >{{ form.errors.favicon }}</FieldError
                                >
                            </Field>
                        </FieldGroup>
                    </CardContent>
                    <CardFooter class="justify-between gap-2 border-t">
                        <Badge variant="secondary">{{
                            imageStatus('favicon')
                        }}</Badge>
                        <Button
                            v-if="canManage && settings.favicon_url"
                            type="button"
                            variant="ghost"
                            size="sm"
                            :disabled="form.processing"
                            @click="toggleRemoval('favicon')"
                        >
                            <Undo2
                                v-if="form.remove_favicon"
                                data-icon="inline-start"
                            />
                            <Trash2 v-else data-icon="inline-start" />
                            {{
                                t(
                                    form.remove_favicon
                                        ? 'common.button.undo'
                                        : 'settings.brand.button.remove_favicon',
                                )
                            }}
                        </Button>
                    </CardFooter>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <ImageIcon
                        class="size-5 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <CardTitle>{{
                        t('settings.brand.label.auth_background')
                    }}</CardTitle>
                    <CardDescription>{{
                        t('settings.brand.hint.auth_background')
                    }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <FieldGroup>
                        <Field :data-invalid="!!form.errors.auth_background">
                            <ImageUploader
                                :src="imageSource('auth_background')"
                                :name="
                                    t('settings.brand.label.auth_background')
                                "
                                kind="background"
                                :title="
                                    t('settings.brand.label.auth_background')
                                "
                                :choose-label="
                                    t(
                                        'settings.brand.button.choose_auth_background',
                                    )
                                "
                                :done-label="t('common.button.done')"
                                :disabled="!canManage || form.processing"
                                :invalid="!!form.errors.auth_background"
                                :reset-key="resetKeys.auth_background"
                                :error="form.errors.auth_background"
                                @selected="
                                    selectImage('auth_background', $event)
                                "
                                @removed="clearImage('auth_background')"
                            />
                            <FieldError
                                v-if="form.errors.auth_background"
                                id="background-error"
                                >{{ form.errors.auth_background }}</FieldError
                            >
                        </Field>
                    </FieldGroup>
                </CardContent>
                <CardFooter class="justify-between gap-2 border-t">
                    <Badge variant="secondary">{{
                        imageStatus('auth_background')
                    }}</Badge>
                    <Button
                        v-if="canManage && settings.auth_background_url"
                        type="button"
                        variant="ghost"
                        size="sm"
                        :disabled="form.processing"
                        @click="toggleRemoval('auth_background')"
                    >
                        <Undo2
                            v-if="form.remove_auth_background"
                            data-icon="inline-start"
                        />
                        <Trash2 v-else data-icon="inline-start" />
                        {{
                            t(
                                form.remove_auth_background
                                    ? 'common.button.undo'
                                    : 'settings.brand.button.remove_auth_background',
                            )
                        }}
                    </Button>
                </CardFooter>
            </Card>

            <Card>
                <CardHeader>
                    <Palette
                        class="size-5 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <CardTitle>{{ t('settings.brand.label.color') }}</CardTitle>
                    <CardDescription>{{
                        t('settings.brand.hint.color')
                    }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <FieldGroup>
                        <Field :data-invalid="!!form.errors.color">
                            <FieldLabel
                                id="brand-color-label"
                                class="sr-only"
                                >{{
                                    t('settings.brand.label.color')
                                }}</FieldLabel
                            >
                            <ToggleGroup
                                v-model="form.color"
                                type="single"
                                variant="outline"
                                :spacing="2"
                                class="grid w-full grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6"
                                aria-labelledby="brand-color-label"
                                :disabled="!canManage"
                            >
                                <ToggleGroupItem
                                    v-for="color in colorPresets"
                                    :key="color.value"
                                    :value="color.value"
                                    :data-test="`brand-color-${color.value}`"
                                    class="h-auto flex-col gap-3 rounded-lg border px-4 py-4 data-[state=on]:border-primary data-[state=on]:ring-2 data-[state=on]:ring-primary/20"
                                >
                                    <span
                                        class="size-8 rounded-full border border-border"
                                        :style="{
                                            backgroundColor: color.swatch,
                                        }"
                                        aria-hidden="true"
                                    />
                                    {{ color.label }}
                                </ToggleGroupItem>
                            </ToggleGroup>
                            <FieldError
                                v-if="form.errors.color"
                                :errors="[form.errors.color]"
                            />
                        </Field>
                    </FieldGroup>
                </CardContent>
            </Card>

            <div v-if="canManage" class="flex flex-col gap-3 sm:items-end">
                <Progress
                    v-if="form.progress"
                    :model-value="form.progress.percentage"
                    :aria-label="t('settings.brand.status.uploading')"
                    class="w-full sm:max-w-xs"
                />
                <Button
                    type="submit"
                    :disabled="form.processing"
                    data-test="save-brand-settings"
                >
                    <Spinner v-if="form.processing" data-icon="inline-start" />
                    <Save v-else data-icon="inline-start" />
                    {{ t('common.button.save') }}
                </Button>
            </div>
        </form>
    </PageContainer>
</template>
