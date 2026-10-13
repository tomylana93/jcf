<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCheck,
    Layers,
    RotateCcw,
    Save,
    Search,
    X,
} from '@lucide/vue';
import { computed, ref, watch, watchEffect } from 'vue';

import { update } from '@/actions/App/Http/Controllers/Permission/UserPermissionController';
import PageContainer from '@/components/layout/PageContainer.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Empty, EmptyHeader, EmptyTitle } from '@/components/ui/empty';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSet,
} from '@/components/ui/field';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslations } from '@/composables/useTranslations';
import { index, edit } from '@/routes/permissions';
import type {
    PermissionOption,
    PermissionUser,
    UpdateUserPermissionsForm,
} from '@/types';

const props = defineProps<{
    user: PermissionUser;
    permissions: PermissionOption[];
}>();
const { t } = useTranslations();
const search = ref('');
const module = ref('all');
const form = useForm<UpdateUserPermissionsForm>({
    permissions: [...props.user.direct_permissions],
});
watch(
    () => props.user,
    (user) => {
        form.defaults({
            permissions: [...user.direct_permissions],
        });
        form.reset();
    },
);
const groups = computed(() => [
    ...new Set(props.permissions.map((permission) => permission.group)),
]);
const filtered = computed(() =>
    props.permissions.filter(
        (permission) =>
            (module.value === 'all' || permission.group === module.value) &&
            `${permission.label} ${permission.value}`
                .toLocaleLowerCase()
                .includes(search.value.trim().toLocaleLowerCase()),
    ),
);
const visibleGroups = computed(() =>
    groups.value.filter((group) =>
        filtered.value.some((permission) => permission.group === group),
    ),
);
const added = computed(() =>
    form.permissions.filter(
        (value) => !props.user.direct_permissions.includes(value),
    ),
);
const removed = computed(() =>
    props.user.direct_permissions.filter(
        (value) => !form.permissions.includes(value),
    ),
);
const label = (value: string): string =>
    props.permissions.find((permission) => permission.value === value)?.label ??
    value;
function toggle(value: string, checked: boolean | 'indeterminate'): void {
    form.permissions =
        checked === true
            ? [...new Set([...form.permissions, value])]
            : form.permissions.filter((permission) => permission !== value);
}
function selectVisible(selected: boolean): void {
    const values = filtered.value.map((permission) => permission.value);
    form.permissions = selected
        ? [...new Set([...form.permissions, ...values])]
        : form.permissions.filter((value) => !values.includes(value));
}
function submit(): void {
    form.submit(update(props.user.id), {
        preserveScroll: true,
    });
}
watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [
            { title: t('breadcrumb.permission'), href: index() },
            { title: props.user.name, href: edit(props.user.id) },
        ],
    }),
);
</script>
<template>
    <PageContainer
        :title="t('permission.card.manage_title')"
        :description="user.name"
    >
        <Head :title="`${t('permission.title')} · ${user.name}`" />
        <Alert v-if="user.super_admin">
            <AlertDescription>{{
                t('permission.helper.all_access')
            }}</AlertDescription>
        </Alert>
        <form
            v-if="!user.super_admin"
            novalidate
            class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]"
            @submit.prevent="submit"
        >
            <div class="flex min-w-0 flex-col gap-6">
                <Card v-if="user.inherited_permissions.length">
                    <CardHeader>
                        <CardTitle>{{
                            t('permission.label.inherited')
                        }}</CardTitle>
                        <CardDescription>{{
                            t('permission.helper.inherited')
                        }}</CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-wrap gap-2">
                        <Badge
                            v-for="value in user.inherited_permissions"
                            :key="value"
                            variant="secondary"
                            >{{ value }}</Badge
                        >
                    </CardContent>
                </Card>
                <FieldGroup class="grid gap-4 sm:grid-cols-2">
                    <Field>
                        <FieldLabel for="permission-search">{{
                            t('permission.label.search')
                        }}</FieldLabel>
                        <InputGroup>
                            <InputGroupInput
                                id="permission-search"
                                v-model="search"
                                type="search"
                                :placeholder="
                                    t('permission.placeholder.search')
                                "
                            />
                            <InputGroupAddon>
                                <Search />
                            </InputGroupAddon>
                        </InputGroup>
                    </Field>
                    <Field>
                        <FieldLabel for="permission-module">{{
                            t('permission.label.module')
                        }}</FieldLabel>
                        <Select v-model="module">
                            <InputGroup>
                                <SelectTrigger
                                    id="permission-module"
                                    data-slot="input-group-control"
                                    class="flex-1 border-0 bg-transparent shadow-none focus-visible:ring-0 dark:bg-transparent"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <InputGroupAddon>
                                    <Layers />
                                </InputGroupAddon>
                            </InputGroup>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="all"
                                        >{{
                                            t(
                                                'permission.placeholder.all_modules',
                                            )
                                        }}
                                    </SelectItem>
                                    <SelectItem
                                        v-for="group in groups"
                                        :key="group"
                                        :value="group"
                                        >{{ group }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </Field>
                </FieldGroup>
                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing || !filtered.length"
                        @click="selectVisible(true)"
                    >
                        <CheckCheck data-icon="inline-start" />
                        {{ t('permission.button.select_visible') }}
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing || !filtered.length"
                        @click="selectVisible(false)"
                    >
                        <X data-icon="inline-start" />
                        {{ t('permission.button.clear_visible') }}
                    </Button>
                    <Badge variant="secondary"
                        >{{ filtered.length }} / {{ permissions.length }}</Badge
                    >
                </div>
                <Empty v-if="!filtered.length">
                    <EmptyHeader>
                        <EmptyTitle>{{
                            t('permission.helper.no_results')
                        }}</EmptyTitle>
                    </EmptyHeader>
                </Empty>
                <FieldGroup>
                    <FieldSet
                        v-for="group in visibleGroups"
                        :key="group"
                        class="rounded-xl border p-4"
                    >
                        <FieldLegend>{{ group }}</FieldLegend>
                        <FieldGroup>
                            <Field
                                v-for="permission in filtered.filter(
                                    (option) => option.group === group,
                                )"
                                :key="permission.value"
                                orientation="horizontal"
                                :data-invalid="form.hasErrors"
                                :data-disabled="form.processing"
                            >
                                <Checkbox
                                    :id="`permission-${permission.value}`"
                                    :model-value="
                                        form.permissions.includes(
                                            permission.value,
                                        )
                                    "
                                    :disabled="form.processing"
                                    :aria-invalid="form.hasErrors"
                                    @update:model-value="
                                        toggle(permission.value, $event)
                                    "
                                />
                                <FieldContent>
                                    <FieldLabel
                                        :for="`permission-${permission.value}`"
                                        >{{ permission.label }}</FieldLabel
                                    >
                                    <FieldDescription>{{
                                        permission.value
                                    }}</FieldDescription>
                                </FieldContent>
                            </Field>
                        </FieldGroup>
                    </FieldSet>
                </FieldGroup>
            </div>
            <Card class="lg:sticky lg:top-6">
                <CardHeader>
                    <CardTitle>{{
                        t('permission.card.summary_title')
                    }}</CardTitle>
                    <CardDescription
                        >{{ t('permission.label.selected') }}:
                        {{
                            form.permissions.filter(
                                (value) =>
                                    !user.inherited_permissions.includes(value),
                            ).length
                        }}</CardDescription
                    >
                </CardHeader>
                <CardContent class="flex flex-col gap-4">
                    <FieldError :errors="Object.values(form.errors)" />
                    <p v-if="!added.length && !removed.length">
                        {{ t('permission.helper.unchanged') }}
                    </p>
                    <FieldGroup v-else>
                        <Field>
                            <FieldLabel
                                >{{ t('permission.label.added') }} ({{
                                    added.length
                                }})</FieldLabel
                            >
                            <ul
                                class="flex max-h-48 flex-col gap-1 overflow-y-auto"
                            >
                                <li v-for="value in added" :key="value">
                                    {{ label(value) }}
                                </li>
                            </ul>
                        </Field>
                        <Field>
                            <FieldLabel
                                >{{ t('permission.label.removed') }} ({{
                                    removed.length
                                }})</FieldLabel
                            >
                            <ul
                                class="flex max-h-48 flex-col gap-1 overflow-y-auto"
                            >
                                <li v-for="value in removed" :key="value">
                                    {{ label(value) }}
                                </li>
                            </ul>
                        </Field>
                    </FieldGroup>
                </CardContent>
                <CardFooter class="flex flex-col gap-2">
                    <Button
                        class="w-full"
                        type="submit"
                        :disabled="
                            form.processing ||
                            (!added.length && !removed.length)
                        "
                        data-test="save-permissions"
                    >
                        <Save data-icon="inline-start" />
                        {{ t('common.button.save') }}
                    </Button>
                    <Button
                        class="w-full"
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="
                            form.reset();
                            form.clearErrors();
                        "
                    >
                        <RotateCcw data-icon="inline-start" />
                        {{ t('permission.button.reset') }}
                    </Button>
                    <Link
                        :href="index()"
                        :class="buttonVariants({ variant: 'outline' })"
                        class="w-full"
                    >
                        <ArrowLeft data-icon="inline-start" />
                        {{ t('permission.button.back') }}
                    </Link>
                </CardFooter>
            </Card>
        </form>
    </PageContainer>
</template>
