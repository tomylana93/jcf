<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

import PageContainer from '@/components/layout/PageContainer.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useDateTime } from '@/composables/useDateTime';
import { useTranslations } from '@/composables/useTranslations';
import { index, show, update } from '@/routes/website/inbox';

import type { ContactRow } from './columns';

const props = defineProps<{
    message: ContactRow & { name: string; email: string; message: string };
}>();
const { t } = useTranslations();
const { formatDateTime } = useDateTime();
const form = useForm({ handled: false });
watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [
            { title: t('contact.title.inbox'), href: index() },
            { title: t('contact.title.detail'), href: show(props.message.id) },
        ],
    }),
);
function toggle(): void {
    form.handled = !props.message.handled_at;
    form.patch(update(props.message.id).url, { preserveScroll: true });
}
</script>
<template>
    <Head :title="t('contact.title.detail')" />

    <PageContainer :title="message.subject">
        <div class="flex max-w-3xl flex-col gap-6">
            <Link
                :href="index()"
                class="self-start underline underline-offset-4"
            >
                {{ t('contact.button.back') }}
            </Link>
            <dl class="grid min-w-0 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-muted-foreground">
                        {{ t('contact.label.name') }}
                    </dt>
                    <dd class="wrap-anywhere">{{ message.name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">
                        {{ t('contact.label.email') }}
                    </dt>
                    <dd class="wrap-anywhere">{{ message.email }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">
                        {{ t('contact.label.received') }}
                    </dt>
                    <dd>{{ formatDateTime(message.created_at) }}</dd>
                </div>
                <div>
                    <dt class="mb-1 text-sm text-muted-foreground">
                        {{ t('contact.label.status') }}
                    </dt>
                    <dd>
                        <StatusBadge
                            :label="
                                t(
                                    message.handled_at
                                        ? 'contact.status.handled'
                                        : 'contact.status.unhandled',
                                )
                            "
                            :tone="message.handled_at ? 'success' : 'neutral'"
                        />
                    </dd>
                </div>
            </dl>
            <p
                class="border-y py-6 leading-7 wrap-anywhere whitespace-pre-wrap"
                data-test="inbox_message"
            >
                {{ message.message }}
            </p>
            <p
                v-if="form.hasErrors"
                role="alert"
                class="text-sm text-destructive"
            >
                {{ t('contact.error.server') }}
            </p>
            <Button
                class="self-start"
                :disabled="form.processing"
                data-test="inbox_toggle"
                @click="toggle"
            >
                {{
                    t(
                        message.handled_at
                            ? 'contact.button.unhandled'
                            : 'contact.button.handled',
                    )
                }}
            </Button>
        </div>
    </PageContainer>
</template>
