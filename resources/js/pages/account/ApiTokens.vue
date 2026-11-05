<script setup lang="ts">
import {
    Head,
    router,
    setLayoutProps,
    useForm,
    usePage,
} from '@inertiajs/vue3';
import { Check, Copy, KeyRound, Trash2 } from '@lucide/vue';
import { useClipboard } from '@vueuse/core';
import { ref, watchEffect } from 'vue';

import ApiTokenController from '@/actions/App/Http/Controllers/Account/ApiTokenController';
import Heading from '@/components/shared/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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
import { useDateTime } from '@/composables/useDateTime';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/api-tokens';
import type { ApiToken, ApiTokenForm, SelectOption } from '@/types';

const props = defineProps<{
    tokens: ApiToken[];
    abilityOptions: SelectOption[];
    expirationOptions: SelectOption[];
}>();

const { t } = useTranslations();
const { formatDateTime } = useDateTime();
const page = usePage();
const { copy, copied } = useClipboard({ legacy: true });
const revokingId = ref<number | null>(null);

const form = useForm<ApiTokenForm>({
    name: '',
    abilities: [props.abilityOptions[0]?.value ?? 'read'],
    expiration:
        props.expirationOptions[props.expirationOptions.length - 1]?.value ??
        '365',
});

watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [{ title: t('account.api_tokens.title'), href: index() }],
    }),
);

function toggleAbility(value: string, checked: boolean | 'indeterminate') {
    form.abilities =
        checked === true
            ? [...new Set([...form.abilities, value])]
            : form.abilities.filter((ability) => ability !== value);
}

function abilityLabel(value: string): string {
    return (
        props.abilityOptions.find((option) => option.value === value)?.label ??
        value
    );
}

function submit() {
    form.submit(ApiTokenController.store(), {
        preserveScroll: true,
        onSuccess: () => form.reset('name'),
    });
}

function revoke(token: ApiToken) {
    revokingId.value = token.id;
    router.delete(ApiTokenController.destroy.url(token.id), {
        preserveScroll: true,
        onFinish: () => (revokingId.value = null),
    });
}
</script>

<template>
    <div class="flex flex-col gap-10">
        <Head :title="t('account.api_tokens.title')" />

        <div class="flex flex-col gap-6">
            <Heading
                variant="small"
                :title="t('account.api_tokens.title')"
                :description="t('account.api_tokens.card.description')"
            />

            <div
                v-if="page.flash.apiToken"
                class="flex flex-col gap-3 rounded-lg border p-4"
                data-test="new-api-token"
            >
                <p class="text-sm font-medium">
                    {{ t('account.api_tokens.label.new_token') }}
                </p>
                <p class="text-sm text-muted-foreground">
                    {{ t('account.api_tokens.helper.copy_once') }}
                </p>
                <code
                    class="rounded-md bg-muted px-3 py-2 font-mono text-sm break-all"
                >
                    {{ page.flash.apiToken }}
                </code>
                <Button
                    type="button"
                    variant="outline"
                    class="self-start"
                    @click="copy(page.flash.apiToken)"
                >
                    <Check
                        v-if="copied"
                        aria-hidden="true"
                    />
                    <Copy
                        v-else
                        aria-hidden="true"
                    />
                    {{
                        copied
                            ? t('account.api_tokens.button.copied')
                            : t('account.api_tokens.button.copy')
                    }}
                </Button>
            </div>

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
                            form.errors.name
                                ? 'api-token-name-error'
                                : undefined
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
                            :model-value="
                                form.abilities.includes(ability.value)
                            "
                            :disabled="form.processing"
                            @update:model-value="
                                toggleAbility(ability.value, $event)
                            "
                        />
                        <FieldLabel
                            :for="`api-token-ability-${ability.value}`"
                            class="font-normal"
                        >
                            {{ ability.label }}
                        </FieldLabel>
                    </Field>
                    <FieldError
                        :errors="[
                            form.errors.abilities,
                            form.errors['abilities.0'],
                        ]"
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
        </div>

        <div class="flex flex-col gap-4">
            <Heading
                variant="small"
                :title="t('account.api_tokens.card.list')"
            />

            <p
                v-if="tokens.length === 0"
                class="text-sm text-muted-foreground"
            >
                {{ t('account.api_tokens.helper.empty') }}
            </p>

            <ul
                v-else
                class="overflow-hidden rounded-lg border"
            >
                <li
                    v-for="token in tokens"
                    :key="token.id"
                    class="flex items-start justify-between gap-4 border-b p-4 last:border-b-0"
                >
                    <div class="flex min-w-0 gap-4">
                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-muted"
                        >
                            <KeyRound class="size-5 text-muted-foreground" />
                        </div>
                        <div class="flex min-w-0 flex-col gap-1">
                            <p class="font-medium wrap-anywhere">
                                {{ token.name }}
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                <Badge
                                    v-for="ability in token.abilities"
                                    :key="ability"
                                    variant="secondary"
                                >
                                    {{ abilityLabel(ability) }}
                                </Badge>
                                <Badge
                                    v-if="token.expired"
                                    variant="destructive"
                                >
                                    {{ t('account.api_tokens.label.expired') }}
                                </Badge>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                {{
                                    t('account.api_tokens.helper.created', {
                                        time: formatDateTime(token.created_at),
                                    })
                                }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{
                                    token.last_used_at
                                        ? t(
                                              'account.api_tokens.helper.last_used',
                                              {
                                                  time: formatDateTime(
                                                      token.last_used_at,
                                                  ),
                                              },
                                          )
                                        : t(
                                              'account.api_tokens.helper.never_used',
                                          )
                                }}
                            </p>
                            <p
                                v-if="token.expires_at"
                                class="text-sm text-muted-foreground"
                            >
                                {{
                                    t('account.api_tokens.helper.expires', {
                                        time: formatDateTime(token.expires_at),
                                    })
                                }}
                            </p>
                        </div>
                    </div>

                    <Dialog>
                        <DialogTrigger as-child>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                            >
                                <Trash2 class="size-4" />
                                <span class="sr-only">
                                    {{ t('account.api_tokens.button.revoke') }}
                                </span>
                            </Button>
                        </DialogTrigger>

                        <DialogContent>
                            <DialogTitle>
                                {{
                                    t('account.api_tokens.dialog.revoke_title')
                                }}
                            </DialogTitle>
                            <DialogDescription>
                                {{ t('account.api_tokens.helper.revoke') }}
                            </DialogDescription>
                            <DialogFooter class="gap-2">
                                <DialogClose as-child>
                                    <Button variant="secondary">
                                        {{ t('common.button.cancel') }}
                                    </Button>
                                </DialogClose>
                                <Button
                                    variant="destructive"
                                    :disabled="revokingId === token.id"
                                    @click="revoke(token)"
                                >
                                    {{ t('account.api_tokens.button.revoke') }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </li>
            </ul>
        </div>
    </div>
</template>
