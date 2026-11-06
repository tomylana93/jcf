<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Plus, Save, X } from '@lucide/vue';
import { computed, watch } from 'vue';

import {
    store,
    update,
} from '@/actions/App/Http/Controllers/Identity/ApiClientController';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    NativeSelect,
    NativeSelectOption,
} from '@/components/ui/native-select';
import {
    Sheet,
    SheetContent,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import { useTranslations } from '@/composables/useTranslations';
import type { ApiClientForm, ApiClientRow, SelectOption } from '@/types';

const props = defineProps<{
    open: boolean;
    client: ApiClientRow | null;
    statuses: SelectOption[];
}>();

const emit = defineEmits<{
    'update:open': [open: boolean];
}>();

const { t } = useTranslations();
const editing = computed(() => props.client !== null);

const form = useForm<ApiClientForm>({
    name: '',
    description: '',
    status: 'active',
});

watch(
    () => [props.open, props.client] as const,
    ([open, client]) => {
        if (!open) {
            return;
        }
        form.clearErrors();
        form.defaults({
            name: client?.name ?? '',
            description: client?.description ?? '',
            status: client?.status ?? 'active',
        });
        form.reset();
    },
    { immediate: true },
);

function submit(): void {
    form.submit(props.client ? update.patch(props.client.id) : store(), {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Sheet
        :open="open"
        @update:open="emit('update:open', $event)"
    >
        <SheetContent
            class="overflow-y-auto sm:max-w-lg"
            :aria-describedby="undefined"
        >
            <form
                novalidate
                class="flex min-h-full flex-col gap-2"
                @submit.prevent="submit"
            >
                <SheetHeader>
                    <SheetTitle>
                        {{
                            editing
                                ? t('api_client.card.edit_title')
                                : t('api_client.card.create_title')
                        }}
                    </SheetTitle>
                </SheetHeader>
                <FieldGroup class="flex-1 px-4 py-6">
                    <Field :data-invalid="!!form.errors.name">
                        <FieldLabel for="api-client-name">
                            {{ t('api_client.label.name') }}
                        </FieldLabel>
                        <Input
                            id="api-client-name"
                            v-model="form.name"
                            maxlength="100"
                            :placeholder="t('api_client.placeholder.name')"
                            :aria-invalid="!!form.errors.name"
                            :aria-describedby="
                                form.errors.name
                                    ? 'api-client-name-error'
                                    : undefined
                            "
                        />
                        <FieldError
                            id="api-client-name-error"
                            :errors="[form.errors.name]"
                        />
                    </Field>
                    <Field :data-invalid="!!form.errors.description">
                        <FieldLabel for="api-client-description">
                            {{ t('api_client.label.description') }}
                        </FieldLabel>
                        <Textarea
                            id="api-client-description"
                            v-model="form.description"
                            maxlength="500"
                            :placeholder="
                                t('api_client.placeholder.description')
                            "
                            :aria-invalid="!!form.errors.description"
                        />
                        <FieldError :errors="[form.errors.description]" />
                    </Field>
                    <Field :data-invalid="!!form.errors.status">
                        <FieldLabel for="api-client-status">
                            {{ t('api_client.label.status') }}
                        </FieldLabel>
                        <NativeSelect
                            id="api-client-status"
                            v-model="form.status"
                            :aria-invalid="!!form.errors.status"
                        >
                            <NativeSelectOption
                                v-for="status in statuses"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </NativeSelectOption>
                        </NativeSelect>
                        <FieldDescription>
                            {{ t('api_client.helper.disabled') }}
                        </FieldDescription>
                        <FieldError :errors="[form.errors.status]" />
                    </Field>
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
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        data-test="save-api-client"
                    >
                        <Save
                            v-if="editing"
                            data-icon="inline-start"
                        />
                        <Plus
                            v-else
                            data-icon="inline-start"
                        />
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
