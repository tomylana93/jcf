<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { KeyRound, Trash2 } from '@lucide/vue';
import { ref } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useDateTime } from '@/composables/useDateTime';
import { useTranslations } from '@/composables/useTranslations';
import type { ApiToken, SelectOption } from '@/types';
import type { RouteDefinition } from '@/wayfinder';

const props = defineProps<{
    tokens: ApiToken[];
    abilityOptions: SelectOption[];
    revokeAction: (token: ApiToken) => RouteDefinition<'delete'>;
    canRevoke?: boolean;
}>();

const { t } = useTranslations();
const { formatDateTime } = useDateTime();
const revokingId = ref<number | null>(null);
const revokeForm = useForm({});

function abilityLabel(value: string): string {
    return (
        props.abilityOptions.find((option) => option.value === value)?.label ??
        value
    );
}

function revoke(token: ApiToken): void {
    revokingId.value = token.id;
    revokeForm.submit(props.revokeAction(token), {
        preserveScroll: true,
        onFinish: () => (revokingId.value = null),
    });
}
</script>

<template>
    <div class="flex flex-col gap-4">
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
                                    ? t('account.api_tokens.helper.last_used', {
                                          time: formatDateTime(
                                              token.last_used_at,
                                          ),
                                      })
                                    : t('account.api_tokens.helper.never_used')
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

                <Dialog v-if="canRevoke !== false">
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
                            {{ t('account.api_tokens.dialog.revoke_title') }}
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
</template>
