<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

import {
    destroy,
    store,
} from '@/actions/App/Http/Controllers/Account/ApiTokenController';
import ApiTokenCreateForm from '@/components/api-tokens/ApiTokenCreateForm.vue';
import ApiTokenList from '@/components/api-tokens/ApiTokenList.vue';
import NewApiTokenNotice from '@/components/api-tokens/NewApiTokenNotice.vue';
import Heading from '@/components/shared/Heading.vue';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/api-tokens';
import type { ApiToken, SelectOption } from '@/types';

defineProps<{
    tokens: ApiToken[];
    abilityOptions: SelectOption[];
    expirationOptions: SelectOption[];
}>();

const { t } = useTranslations();

const revokeAction = (token: ApiToken) => destroy(token.id);

watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [{ title: t('account.api_tokens.title'), href: index() }],
    }),
);
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
            <NewApiTokenNotice />
            <ApiTokenCreateForm
                :action="store()"
                :ability-options="abilityOptions"
                :expiration-options="expirationOptions"
            />
        </div>

        <div class="flex flex-col gap-4">
            <Heading
                variant="small"
                :title="t('account.api_tokens.card.list')"
            />
            <ApiTokenList
                :tokens="tokens"
                :ability-options="abilityOptions"
                :revoke-action="revokeAction"
            />
        </div>
    </div>
</template>
