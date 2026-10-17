<script setup lang="ts">
import { Head, Link, router, setLayoutProps, usePage } from '@inertiajs/vue3';
import { ChevronRight, ExternalLink, Globe, Settings } from '@lucide/vue';
import { computed, watchEffect } from 'vue';

import { applyPreset } from '@/actions/App/Http/Controllers/Website/WebsiteSettingsController';
import PageContainer from '@/components/layout/PageContainer.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import WebsitePresetPicker from '@/components/website/WebsitePresetPicker.vue';
import { useTranslations } from '@/composables/useTranslations';
import { preview, index as websiteIndex } from '@/routes/website';
import { edit } from '@/routes/website/page';
import { edit as settingsEdit } from '@/routes/website/settings';
import type { WebsiteArea, WebsitePageStatus, WebsitePresetKey } from '@/types';

const props = defineProps<{
    area: WebsiteArea;
}>();

const { t } = useTranslations();

const page = usePage();

const canConfigure = computed(() =>
    page.props.auth.permissions.includes('website.configure'),
);

const canEdit = computed(
    () =>
        page.props.auth.permissions.includes('website.content.edit') ||
        page.props.auth.permissions.includes('website.content.publish'),
);

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [{ title: t('breadcrumb.website'), href: websiteIndex() }],
    });
});

function statusLabel(status: WebsitePageStatus | null): string {
    if (status === null) {
        return t('website.status.not_started');
    }

    return status.published
        ? t('website.status.published')
        : t('website.status.draft_only');
}

function applyPresetTo(preset: WebsitePresetKey): void {
    router.patch(applyPreset().url, { preset }, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('website.title.index')" />

    <PageContainer
        :title="t('website.card.title')"
        :description="t('website.card.description')"
    >
        <div class="flex w-full flex-col gap-6">
            <WebsitePresetPicker
                v-if="canConfigure"
                :preset="props.area.preset"
                :options="props.area.presetOptions"
                :confirm-required="true"
                @apply="applyPresetTo"
            />

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('website.card.page.title') }}</CardTitle>
                    <CardDescription>
                        {{ t('website.card.page.description') }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="flex flex-col gap-3">
                    <div
                        v-for="item in props.area.pages"
                        :key="item.page"
                        class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-4"
                    >
                        <div class="flex min-w-0 flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <span class="truncate text-sm font-semibold">
                                    {{ item.label }}
                                </span>
                                <StatusBadge
                                    :label="statusLabel(item.status)"
                                    :tone="
                                        item.status?.published
                                            ? 'success'
                                            : 'neutral'
                                    "
                                    :data-test="`website_page_status_${item.page}`"
                                />
                            </div>
                            <span class="text-sm text-muted-foreground">
                                {{ t('website.card.page.description') }}
                            </span>
                            <span
                                v-if="item.status?.published"
                                class="text-xs text-muted-foreground"
                            >
                                {{ t('website.label.published_at') }}
                                {{
                                    new Date(
                                        item.status.published_at ?? '',
                                    ).toLocaleString()
                                }}
                            </span>
                        </div>
                        <div
                            v-if="canEdit"
                            class="flex items-center gap-2"
                        >
                            <Button
                                as-child
                                size="sm"
                                variant="outline"
                            >
                                <a
                                    :href="
                                        preview(
                                            { page: item.page },
                                            {
                                                query: {
                                                    preset: props.area.preset,
                                                },
                                            },
                                        ).url
                                    "
                                    target="_blank"
                                    rel="noopener"
                                    :data-test="`website_page_preview_${item.page}`"
                                >
                                    <ExternalLink class="size-4" />
                                    {{ t('website.button.preview') }}
                                </a>
                            </Button>
                            <Button
                                as-child
                                size="sm"
                                variant="outline"
                            >
                                <Link
                                    :href="edit({ page: item.page })"
                                    :data-test="`website_page_edit_${item.page}`"
                                >
                                    {{
                                        t('website.title.content', {
                                            label: item.label,
                                        })
                                    }}
                                    <ChevronRight class="size-4" />
                                </Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card v-if="canConfigure">
                <CardHeader>
                    <CardTitle>
                        {{ t('website.card.settings.title') }}
                    </CardTitle>
                    <CardDescription>
                        {{ t('website.card.settings.description') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Button
                        as-child
                        size="sm"
                        variant="outline"
                        data-test="website_settings_link"
                    >
                        <Link :href="settingsEdit()">
                            <Settings class="size-4" />
                            {{ t('website.card.settings.title') }}
                        </Link>
                    </Button>
                </CardContent>
            </Card>

            <Alert
                v-if="!props.area.publicPagesEnabled"
                class="border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-300 dark:bg-amber-950 dark:text-amber-200"
            >
                <Globe class="size-4" />
                <AlertDescription>
                    {{ t('website.card.public_pages_disabled') }}
                </AlertDescription>
            </Alert>
        </div>
    </PageContainer>
</template>
