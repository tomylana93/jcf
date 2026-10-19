<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

import { useTranslations } from '@/composables/useTranslations';
import { store } from '@/routes/contact';

const { t } = useTranslations();
const form = useHttp({
    name: '',
    email: '',
    subject: '',
    message: '',
    website_url: '',
});
const feedback = ref('');
const sent = ref(false);
const root = ref<HTMLFormElement | null>(null);
const fields = ['name', 'email', 'subject', 'message'] as const;
const limits = { name: 150, email: 254, subject: 200, message: 5000 };

async function submit(): Promise<void> {
    if (form.processing) return;
    feedback.value = '';
    sent.value = false;
    await form
        .post(store.url(), {
            onSuccess: () => {
                sent.value = true;
                feedback.value = t('contact.status.sent');
                form.resetAndClearErrors();
            },
            onError: async () => {
                feedback.value = t('contact.error.invalid');
                await nextTick();
                root.value
                    ?.querySelector<HTMLElement>('[aria-invalid="true"]')
                    ?.focus();
            },
            onHttpException: (response) => {
                feedback.value = t(
                    response.status === 429
                        ? 'contact.error.throttled'
                        : response.status === 419
                          ? 'contact.error.session'
                          : 'contact.error.server',
                );
                return false;
            },
            onNetworkError: () => {
                feedback.value = t('contact.error.network');
                return false;
            },
        })
        .catch(() => undefined);
}
</script>

<template>
    <form
        ref="root"
        class="flex min-w-0 flex-col gap-6"
        @submit.prevent="submit"
    >
        <div class="flex flex-col gap-2">
            <h2 class="text-2xl font-semibold tracking-tight">
                {{ t('contact.title.form') }}
            </h2>
            <p class="max-w-prose text-sm leading-6 text-muted-foreground">
                {{ t('contact.helper.form') }}
            </p>
        </div>
        <div
            v-for="field in fields"
            :key="field"
            class="flex min-w-0 flex-col gap-2"
        >
            <label
                :for="`contact-${field}`"
                class="text-sm font-medium"
            >
                {{ t(`contact.label.${field}`) }}
            </label>
            <textarea
                v-if="field === 'message'"
                :id="`contact-${field}`"
                v-model="form[field]"
                :name="field"
                required
                :maxlength="limits[field]"
                rows="6"
                :aria-invalid="!!form.errors[field]"
                :aria-describedby="
                    form.errors[field] ? `contact-${field}-error` : undefined
                "
                class="w-full min-w-0 rounded-md border border-input bg-background px-3 py-2 text-base focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
            />
            <input
                v-else
                :id="`contact-${field}`"
                v-model="form[field]"
                :name="field"
                :type="field === 'email' ? 'email' : 'text'"
                :autocomplete="field === 'subject' ? 'off' : field"
                required
                :maxlength="limits[field]"
                :aria-invalid="!!form.errors[field]"
                :aria-describedby="
                    form.errors[field] ? `contact-${field}-error` : undefined
                "
                class="min-h-11 w-full min-w-0 rounded-md border border-input bg-background px-3 py-2 text-base focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
            />
            <p
                v-if="form.errors[field]"
                :id="`contact-${field}-error`"
                class="text-sm text-destructive"
            >
                {{ form.errors[field] }}
            </p>
        </div>
        <div
            hidden
            aria-hidden="true"
        >
            <label for="contact-website-url">
                {{ t('contact.label.website_url') }}
            </label>
            <input
                id="contact-website-url"
                v-model="form.website_url"
                name="website_url"
                tabindex="-1"
                autocomplete="off"
            />
        </div>
        <p class="max-w-prose text-sm leading-6 text-muted-foreground">
            {{ t('contact.helper.privacy') }}
        </p>
        <p
            v-if="feedback"
            role="status"
            aria-live="polite"
            :class="[
                'text-sm leading-6',
                sent ? 'text-foreground' : 'text-destructive',
            ]"
            data-test="contact_feedback"
        >
            {{ feedback }}
        </p>
        <button
            type="submit"
            :disabled="form.processing"
            class="min-h-11 self-start rounded-md bg-primary px-5 py-3 text-sm font-medium text-primary-foreground hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:cursor-wait disabled:opacity-60"
            data-test="contact_send"
        >
            {{
                t(
                    form.processing
                        ? 'contact.button.sending'
                        : 'contact.button.send',
                )
            }}
        </button>
    </form>
</template>
