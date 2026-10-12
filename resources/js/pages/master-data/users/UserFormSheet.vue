<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Mail,
    Plus,
    Save,
    Shield,
    UserRound,
    X,
} from '@lucide/vue';
import { computed, watch } from 'vue';

import {
    store,
    update,
} from '@/actions/App/Http/Controllers/MasterData/UserController';
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
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useTranslations } from '@/composables/useTranslations';
import type { SelectOption, UserForm, UserRow } from '@/types';

const props = defineProps<{
    open: boolean;
    user: UserRow | null;
    roles: SelectOption[];
    statuses: SelectOption[];
}>();
const emit = defineEmits<{
    'update:open': [open: boolean];
}>();
const { t } = useTranslations();
const editing = computed(() => props.user !== null);
const form = useForm<UserForm>({
    password: '',
    password_confirmation: '',
    name: '',
    email: '',
    role: '',
    status: 'active',
}).withPrecognition(() => (props.user ? update.patch(props.user.id) : store()));

watch(
    () => [props.open, props.user] as const,
    ([open, user]) => {
        if (!open) {
            form.reset('password', 'password_confirmation');
            return;
        }
        form.clearErrors();
        form.defaults({
            password: '',
            password_confirmation: '',
            name: user?.name ?? '',
            email: user?.email ?? '',
            role: user?.roles[0]?.name ?? '',
            status: user?.status ?? 'active',
        });
        form.reset();
    },
);

function submit(): void {
    const action = props.user ? update.patch(props.user.id) : store();
    form.submit(action, {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    });
}
</script>
<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent class="overflow-y-auto sm:max-w-lg">
            <form
                novalidate
                class="flex min-h-full flex-col gap-2"
                @submit.prevent="submit"
            >
                <SheetHeader>
                    <SheetTitle>{{
                        editing
                            ? t('user.card.edit_title')
                            : t('user.card.create_title')
                    }}</SheetTitle>
                    <SheetDescription>{{
                        t('user.card.description')
                    }}</SheetDescription>
                </SheetHeader>
                <FieldGroup class="flex-1 px-4 py-6">
                    <Field :data-invalid="!!form.errors.name">
                        <FieldLabel for="user-name">{{
                            t('user.label.name')
                        }}</FieldLabel>
                        <InputGroup>
                            <InputGroupInput
                                id="user-name"
                                v-model="form.name"
                                :placeholder="t('user.placeholder.name')"
                                :aria-invalid="!!form.errors.name"
                                :aria-describedby="
                                    form.errors.name
                                        ? 'user-name-error'
                                        : undefined
                                "
                                autocomplete="name"
                                @blur="form.validate('name')"
                            />
                            <InputGroupAddon>
                                <UserRound />
                            </InputGroupAddon>
                        </InputGroup>
                        <FieldError
                            id="user-name-error"
                            :errors="[form.errors.name]"
                        />
                    </Field>
                    <Field :data-invalid="!!form.errors.email">
                        <FieldLabel for="user-email">{{
                            t('user.label.email')
                        }}</FieldLabel>
                        <InputGroup>
                            <InputGroupInput
                                id="user-email"
                                v-model="form.email"
                                :placeholder="t('user.placeholder.email')"
                                :aria-invalid="!!form.errors.email"
                                :aria-describedby="
                                    form.errors.email
                                        ? 'user-email-error'
                                        : undefined
                                "
                                type="email"
                                autocomplete="email"
                                @blur="form.validate('email')"
                            />
                            <InputGroupAddon>
                                <Mail />
                            </InputGroupAddon>
                        </InputGroup>
                        <FieldError
                            id="user-email-error"
                            :errors="[form.errors.email]"
                        />
                    </Field>
                    <Field :data-invalid="!!form.errors.role">
                        <FieldLabel for="user-role">{{
                            t('user.label.role')
                        }}</FieldLabel>
                        <Select
                            :model-value="
                                form.role
                                    ? `role:${form.role}`
                                    : '__unassigned__'
                            "
                            @update:model-value="
                                (value) => {
                                    form.role =
                                        value === '__unassigned__'
                                            ? ''
                                            : String(value).slice(5);
                                    form.validate('role');
                                }
                            "
                        >
                            <InputGroup>
                                <SelectTrigger
                                    id="user-role"
                                    :aria-invalid="!!form.errors.role"
                                    :aria-describedby="
                                        form.errors.role
                                            ? 'user-role-error'
                                            : undefined
                                    "
                                    data-slot="input-group-control"
                                    class="flex-1 border-0 bg-transparent shadow-none focus-visible:ring-0 dark:bg-transparent"
                                >
                                    <SelectValue
                                        :placeholder="
                                            t('user.placeholder.role')
                                        "
                                    />
                                </SelectTrigger>
                                <InputGroupAddon>
                                    <Shield />
                                </InputGroupAddon>
                            </InputGroup>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="__unassigned__">{{
                                        t('user.label.no_role')
                                    }}</SelectItem>
                                    <SelectItem
                                        v-for="role in roles"
                                        :key="role.value"
                                        :value="`role:${role.value}`"
                                        >{{ role.label }}</SelectItem
                                    >
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <FieldError
                            id="user-role-error"
                            :errors="[form.errors.role]"
                        />
                    </Field>
                    <Field v-if="editing" :data-invalid="!!form.errors.status">
                        <FieldLabel for="user-status">{{
                            t('user.label.status')
                        }}</FieldLabel>
                        <Select
                            v-model="form.status"
                            @update:model-value="form.validate('status')"
                        >
                            <InputGroup>
                                <SelectTrigger
                                    id="user-status"
                                    :aria-invalid="!!form.errors.status"
                                    :aria-describedby="
                                        form.errors.status
                                            ? 'user-status-error'
                                            : undefined
                                    "
                                    data-slot="input-group-control"
                                    class="flex-1 border-0 bg-transparent shadow-none focus-visible:ring-0 dark:bg-transparent"
                                >
                                    <SelectValue
                                        :placeholder="
                                            t('user.placeholder.status')
                                        "
                                    />
                                </SelectTrigger>
                                <InputGroupAddon>
                                    <BadgeCheck />
                                </InputGroupAddon>
                            </InputGroup>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem
                                        v-for="status in statuses"
                                        :key="status.value"
                                        :value="status.value"
                                        >{{ status.label }}</SelectItem
                                    >
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <FieldError
                            id="user-status-error"
                            :errors="[form.errors.status]"
                        />
                    </Field>
                    <template v-if="!editing">
                        <Field :data-invalid="!!form.errors.password">
                            <FieldLabel for="user-password">{{
                                t('user.label.password')
                            }}</FieldLabel>
                            <InputGroup
                                ><InputGroupInput
                                    id="user-password"
                                    v-model="form.password"
                                    type="password"
                                    autocomplete="new-password"
                                    :aria-invalid="!!form.errors.password"
                                    aria-describedby="user-password-error"
                            /></InputGroup>
                            <FieldError
                                id="user-password-error"
                                :errors="[form.errors.password]"
                            />
                        </Field>
                        <Field
                            :data-invalid="!!form.errors.password_confirmation"
                        >
                            <FieldLabel for="user-password-confirmation">{{
                                t('user.label.password_confirmation')
                            }}</FieldLabel>
                            <InputGroup
                                ><InputGroupInput
                                    id="user-password-confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                    :aria-invalid="
                                        !!form.errors.password_confirmation
                                    "
                                    aria-describedby="user-password-confirmation-error"
                            /></InputGroup>
                            <FieldError
                                id="user-password-confirmation-error"
                                :errors="[form.errors.password_confirmation]"
                            />
                        </Field>
                    </template>
                </FieldGroup>
                <SheetFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        <X data-icon="inline-start" />
                        {{ t('common.button.cancel') }}
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Save v-if="editing" data-icon="inline-start" />
                        <Plus v-else data-icon="inline-start" />
                        {{
                            editing
                                ? t('common.button.save')
                                : t('common.button.create')
                        }}
                    </Button>
                </SheetFooter>
            </form>
        </SheetContent>
    </Sheet>
</template>
