<script setup lang="ts">
import { Head, setLayoutProps, usePage } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import { computed, ref, watchEffect } from 'vue';

import {
    destroyToken,
    storeToken,
} from '@/actions/App/Http/Controllers/Identity/ApiClientController';
import ApiTokenCreateForm from '@/components/api-tokens/ApiTokenCreateForm.vue';
import ApiTokenList from '@/components/api-tokens/ApiTokenList.vue';
import NewApiTokenNotice from '@/components/api-tokens/NewApiTokenNotice.vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import Heading from '@/components/shared/Heading.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { index as masterDataIndex } from '@/routes/master-data';
import { index, show } from '@/routes/master-data/api-clients';
import type { ApiClientRow, ApiToken, SelectOption } from '@/types';

import ApiClientFormSheet from './ApiClientFormSheet.vue';
import { apiClientStatusTones } from './columns';

const props = defineProps<{
    client: ApiClientRow;
    tokens: ApiToken[];
    statuses: SelectOption[];
    abilityOptions: SelectOption[];
    expirationOptions: SelectOption[];
}>();

const page = usePage();
const { t } = useTranslations();
const formOpen = ref(false);

const revokeUrl = (token: ApiToken): string =>
    destroyToken.url({ apiClient: props.client.id, token: token.id });

const canManage = computed(() =>
    page.props.auth.permissions.includes('api-clients.manage'),
);

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: t('breadcrumb.master_data'), href: masterDataIndex() },
            { title: t('breadcrumb.api_client'), href: index() },
            { title: props.client.name, href: show(props.client.id) },
        ],
    });
});
</script>

<template>
    <Head :title="client.name" />

    <PageContainer
        :title="client.name"
        :description="client.description ?? t('api_client.card.description')"
    >
        <div class="flex max-w-2xl flex-col gap-10">
            <div class="flex flex-wrap items-center gap-3">
                <StatusBadge
                    :tone="apiClientStatusTones[client.status]"
                    :label="client.status_label"
                />
                <Button
                    v-if="canManage"
                    variant="outline"
                    size="sm"
                    data-test="edit-api-client"
                    @click="formOpen = true"
                >
                    <Pencil data-icon="inline-start" />
                    {{ t('api_client.card.edit_title') }}
                </Button>
            </div>

            <div
                v-if="canManage"
                class="flex flex-col gap-6"
            >
                <Heading
                    variant="small"
                    :title="t('api_client.card.tokens')"
                    :description="t('api_client.card.tokens_description')"
                />
                <NewApiTokenNotice />
                <ApiTokenCreateForm
                    :action="storeToken(client.id)"
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
                    :can-revoke="canManage"
                    :revoke-url="revokeUrl"
                />
            </div>
        </div>

        <ApiClientFormSheet
            v-if="canManage"
            v-model:open="formOpen"
            :client="client"
            :statuses="statuses"
        />
    </PageContainer>
</template>
