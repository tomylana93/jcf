<script setup lang="ts">
import { Head, useForm, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

import { destroy } from '@/actions/App/Http/Controllers/Account/SessionController';
import Heading from '@/components/shared/Heading.vue';
import PasswordInput from '@/components/shared/PasswordInput.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { FieldError } from '@/components/ui/field';
import { useDateTime } from '@/composables/useDateTime';
import { useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/sessions';
import type { AccountSession, ConfirmPasswordForm } from '@/types';
defineProps<{ sessions: AccountSession[]; canListSessions: boolean }>();
const { t } = useTranslations();
const { formatDateTime } = useDateTime();
const form = useForm<ConfirmPasswordForm>({ password: '' });
watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [{ title: t('account.sessions.title'), href: index() }],
    }),
);
function submit() {
    form.submit(destroy(), {
        preserveScroll: true,
        onFinish: () => form.reset('password'),
    });
}
</script>
<template>
    <div class="flex flex-col gap-6">
        <Head :title="t('account.sessions.title')" />
        <Heading
            variant="small"
            :title="t('account.sessions.title')"
            :description="t('account.sessions.card.description')"
        />
        <p v-if="!canListSessions" class="text-sm text-muted-foreground">
            {{ t('account.sessions.helper.unsupported') }}
        </p>
        <p
            v-else-if="sessions.length === 0"
            class="text-sm text-muted-foreground"
        >
            {{ t('account.sessions.helper.empty') }}
        </p>
        <ul class="flex flex-col gap-3">
            <li
                v-for="(session, i) in sessions"
                :key="i"
                class="flex flex-col gap-2 rounded-lg border p-4"
            >
                <p class="text-sm wrap-anywhere">{{ session.device }}</p>
                <Badge v-if="session.current" variant="secondary">{{
                    t('account.sessions.label.current')
                }}</Badge>
                <p class="text-sm text-muted-foreground">
                    {{
                        session.ip_address ??
                        t('account.sessions.label.unknown')
                    }}
                    ?
                    {{ formatDateTime(session.last_active_at) }}
                </p>
            </li>
        </ul>
        <form novalidate @submit.prevent="submit" class="flex flex-col gap-3">
            <p class="text-sm text-muted-foreground">
                {{ t('account.sessions.helper.revoke') }}
            </p>
            <PasswordInput
                id="session_password"
                v-model="form.password"
                autocomplete="current-password"
                :aria-label="t('user.label.current_password')"
                :placeholder="t('user.placeholder.current_password')"
                :aria-invalid="!!form.errors.password"
                aria-describedby="session-password-error"
            />
            <FieldError
                id="session-password-error"
                :errors="[form.errors.password]"
            />
            <Button :disabled="form.processing">{{
                t('account.sessions.button.revoke')
            }}</Button>
        </form>
    </div>
</template>
