<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldError,
    FieldLabel,
    FieldLegend,
    FieldSet,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    NativeSelect,
    NativeSelectOption,
} from '@/components/ui/native-select';
import { useTranslations } from '@/composables/useTranslations';
import type { ApiTokenForm, SelectOption } from '@/types';
import type { RouteDefinition } from '@/wayfinder';

const props = defineProps<{
    action: RouteDefinition<'post'>;
    abilityOptions: SelectOption[];
    expirationOptions: SelectOption[];
}>();

const { t } = useTranslations();

const form = useForm<ApiTokenForm>({
    name: '',
    abilities: [props.abilityOptions[0]?.value ?? 'read'],
    expiration:
        props.expirationOptions[props.expirationOptions.length - 1]?.value ??
        '365',
});

function toggleAbility(
    value: string,
    checked: boolean | 'indeterminate',
): void {
    form.abilities =
        checked === true
            ? [...new Set([...form.abilities, value])]
            : form.abilities.filter((ability) => ability !== value);
}

function submit(): void {
    form.submit(props.action, {
        preserveScroll: true,
        onSuccess: () => form.reset('name'),
    });
}
</script>

<template>
    <form
        novalidate
        class="flex flex-col gap-6"
        @submit.prevent="submit"
    >
        <Field :data-invalid="Boolean(form.errors.name)">
            <FieldLabel for="api_token_name">
                {{ t('account.api_tokens.label.name') }}
            </FieldLabel>
            <Input
                id="api_token_name"
                v-model="form.name"
                maxlength="100"
                :placeholder="t('account.api_tokens.placeholder.name')"
                :aria-invalid="Boolean(form.errors.name)"
                :aria-describedby="
                    form.errors.name ? 'api-token-name-error' : undefined
                "
                :disabled="form.processing"
            />
            <FieldError
                id="api-token-name-error"
                :errors="[form.errors.name]"
            />
        </Field>

        <FieldSet>
            <FieldLegend variant="label">
                {{ t('account.api_tokens.label.abilities') }}
            </FieldLegend>
            <Field
                v-for="ability in abilityOptions"
                :key="ability.value"
                orientation="horizontal"
                :data-invalid="Boolean(form.errors.abilities)"
            >
                <Checkbox
                    :id="`api-token-ability-${ability.value}`"
                    :model-value="form.abilities.includes(ability.value)"
                    :disabled="form.processing"
                    @update:model-value="toggleAbility(ability.value, $event)"
                />
                <FieldLabel
                    :for="`api-token-ability-${ability.value}`"
                    class="font-normal"
                >
                    {{ ability.label }}
                </FieldLabel>
            </Field>
            <FieldError
                :errors="[form.errors.abilities, form.errors['abilities.0']]"
            />
        </FieldSet>

        <Field :data-invalid="Boolean(form.errors.expiration)">
            <FieldLabel for="api_token_expiration">
                {{ t('account.api_tokens.label.expiration') }}
            </FieldLabel>
            <NativeSelect
                id="api_token_expiration"
                v-model="form.expiration"
                :aria-invalid="Boolean(form.errors.expiration)"
                :disabled="form.processing"
            >
                <NativeSelectOption
                    v-for="option in expirationOptions"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </NativeSelectOption>
            </NativeSelect>
            <FieldError :errors="[form.errors.expiration]" />
        </Field>

        <Button
            class="self-start"
            :disabled="form.processing"
        >
            {{ t('account.api_tokens.button.create') }}
        </Button>
    </form>
</template>
