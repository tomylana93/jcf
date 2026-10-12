<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ShieldCheck } from '@lucide/vue';
import { onUnmounted, ref } from 'vue';

import {
    destroy as disable,
    store as enable,
} from '@/actions/Laravel/Fortify/Http/Controllers/TwoFactorAuthenticationController';
import TwoFactorRecoveryCodes from '@/components/auth/two-factor/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/auth/two-factor/TwoFactorSetupModal.vue';
import Heading from '@/components/shared/Heading.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import type { ManageTwoFactorProps } from '@/types';

const { t } = useTranslations();

withDefaults(defineProps<ManageTwoFactorProps>(), {
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();

const showSetupModal = ref<boolean>(false);

onUnmounted(() => clearTwoFactorAuthData());
const enableForm = useForm({});

const disableForm = useForm({});

const enableTwoFactor = () => {
    enableForm.submit(enable(), {
        onSuccess: () => {
            showSetupModal.value = true;
        },
    });
};
</script>

<template>
    <div v-if="canManageTwoFactor" class="space-y-6">
        <Heading
            variant="small"
            :title="t('account.two_factor.card.title')"
            :description="t('account.two_factor.card.description')"
        />

        <div
            v-if="!twoFactorEnabled"
            class="flex flex-col items-start justify-start space-y-4"
        >
            <p class="text-sm text-muted-foreground">
                {{ t('account.two_factor.helper.disabled') }}
            </p>

            <div>
                <Button v-if="hasSetupData" @click="showSetupModal = true">
                    <ShieldCheck />{{ t('account.two_factor.button.setup') }}
                </Button>
                <form v-else @submit.prevent="enableTwoFactor">
                    <Button type="submit" :disabled="enableForm.processing">
                        {{ t('account.two_factor.button.enable') }}
                    </Button>
                </form>
            </div>
        </div>

        <div v-else class="flex flex-col items-start justify-start space-y-4">
            <p class="text-sm text-muted-foreground">
                {{ t('account.two_factor.helper.enabled') }}
            </p>

            <div class="relative inline">
                <form @submit.prevent="disableForm.submit(disable())">
                    <Button
                        variant="destructive"
                        type="submit"
                        :disabled="disableForm.processing"
                    >
                        {{ t('account.two_factor.button.disable') }}
                    </Button>
                </form>
            </div>

            <TwoFactorRecoveryCodes />
        </div>

        <TwoFactorSetupModal
            v-model:isOpen="showSetupModal"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />
    </div>
</template>
