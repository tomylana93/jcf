<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Check, Copy } from '@lucide/vue';
import { useClipboard } from '@vueuse/core';

import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();
const page = usePage();
const { copy, copied } = useClipboard({ legacy: true });
</script>

<template>
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
        <code class="rounded-md bg-muted px-3 py-2 font-mono text-sm break-all">
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
</template>
