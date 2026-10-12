<script setup lang="ts">
import { useForm, Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

import { store as send } from '@/actions/Laravel/Fortify/Http/Controllers/EmailVerificationNotificationController';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import { logout } from '@/routes';

const { t } = useTranslations();

watchEffect(() => {
    setLayoutProps({
        title: t('auth.verify_email.card.title'),
        description: t('auth.verify_email.card.description'),
    });
});

defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.submit(send(), {});
};
</script>

<template>
    <div>
        <Head :title="t('auth.verify_email.title')" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ t('auth.verify_email.status.sent') }}
        </div>

        <form novalidate @submit.prevent="submit" class="flex flex-col gap-6">
            <Button
                type="submit"
                :disabled="form.processing"
                variant="secondary"
            >
                <Spinner v-if="form.processing" />
                {{ t('common.button.resend') }}
            </Button>

            <Separator />

            <Button :as="Link" :href="logout()" variant="ghost">
                {{ t('auth.button.logout') }}
            </Button>
        </form>
    </div>
</template>
