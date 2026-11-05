<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { KeyRound } from '@lucide/vue';

import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
import PasskeyItem from '@/components/auth/passkeys/PasskeyItem.vue';
import PasskeyRegister from '@/components/auth/passkeys/PasskeyRegister.vue';
import Heading from '@/components/shared/Heading.vue';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { useTranslations } from '@/composables/useTranslations';
import type { ManagePasskeysProps } from '@/types';

const { t } = useTranslations();

withDefaults(defineProps<ManagePasskeysProps>(), {
    canManagePasskeys: false,
    passkeys: () => [],
});

const deleteForm = useForm({});

function handleDelete(id: number, onError: () => void): void {
    deleteForm.submit(destroy(id), {
        preserveScroll: true,
        onError,
    });
}

function handleRegisterSuccess(): void {
    router.reload();
}
</script>

<template>
    <div
        v-if="canManagePasskeys"
        class="space-y-6"
    >
        <Heading
            variant="small"
            :title="t('account.passkey.card.title')"
            :description="t('account.passkey.card.description')"
        />

        <div class="overflow-hidden rounded-lg border border-border">
            <template v-if="passkeys.length">
                <PasskeyItem
                    v-for="passkey in passkeys"
                    :key="passkey.id"
                    :passkey="passkey"
                    @remove="handleDelete"
                />
            </template>

            <Empty v-else>
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <KeyRound />
                    </EmptyMedia>
                    <EmptyTitle>
                        {{ t('account.passkey.empty.title') }}
                    </EmptyTitle>
                    <EmptyDescription>
                        {{ t('account.passkey.empty.description') }}
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>
        </div>

        <PasskeyRegister @success="handleRegisterSuccess" />
    </div>
</template>
