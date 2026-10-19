<script setup lang="ts">
import {
    Head,
    Link,
    router,
    setLayoutProps,
    useForm,
    usePage,
} from '@inertiajs/vue3';
import {
    computed,
    defineAsyncComponent,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
    watchEffect,
} from 'vue';

import PageContainer from '@/components/layout/PageContainer.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import WebsiteImageField from '@/components/website/WebsiteImageField.vue';
import WebsiteSeoFields from '@/components/website/WebsiteSeoFields.vue';
import { useTranslations } from '@/composables/useTranslations';
import { emptyEditorDocument } from '@/lib/editor';
import { index as websiteIndex } from '@/routes/website';
import {
    image,
    index,
    preview,
    publish,
    save,
    store,
    unpublish,
} from '@/routes/website/articles';
import type { WebsiteMedia, WebsitePageContentForm } from '@/types';

type ArticleForm = WebsitePageContentForm & {
    slug: string;
    featured: boolean;
    author_id: string;
};
type Editor = {
    id: string | null;
    content: ArticleForm | null;
    published: boolean;
    published_at: string | null;
    images: WebsiteMedia[];
    authors: { value: string; label: string }[];
};
const props = defineProps<{ editor: Editor }>();
const { t } = useTranslations();
const page = usePage();
const RichTextEditor = defineAsyncComponent(
    () => import('@/components/editor/RichTextEditor.vue'),
);
const mounted = ref(false);
const bodyEditor = ref<{ prepareSave: () => boolean } | null>(null);
const sourceDirty = ref(false);
const canEdit = computed(() =>
    page.props.auth.permissions.includes('website.content.edit'),
);
const canPublish = computed(() =>
    page.props.auth.permissions.includes('website.content.publish'),
);
// Vue props may be reactive proxies; JSON cloning keeps form data serializable.
const initial = (): ArticleForm =>
    JSON.parse(
        JSON.stringify(
            props.editor.content ?? {
                title: '',
                slug: '',
                excerpt: '',
                body_json: emptyEditorDocument(),
                sections: [],
                seo: {
                    title: '',
                    description: '',
                    robots: 'index,follow',
                    image: null,
                },
                cover: null,
                version: 0,
                featured: false,
                author_id: page.props.auth.user.id,
            },
        ),
    );
const form = useForm<ArticleForm>(initial());
const publication = useForm({
    version: props.editor.content?.version ?? 0,
    published_at: '',
});
const dirty = computed(() => form.isDirty || sourceDirty.value);
const uploading = ref(false);
watch(
    () => [props.editor.id, props.editor.content?.version],
    (current, previous) => {
        if (current[0] === previous[0] && current[1] === previous[1]) return;
        form.defaults(initial());
        form.reset();
        publication.version = props.editor.content?.version ?? 0;
        sourceDirty.value = false;
    },
);
function submit(): void {
    if (!canEdit.value || !bodyEditor.value?.prepareSave()) return;
    form.submit(props.editor.id ? save(props.editor.id) : store(), {
        preserveScroll: true,
    });
}
function publishDraft(): void {
    if (
        !props.editor.id ||
        dirty.value ||
        !window.confirm(t('website.helper.publish_state'))
    )
        return;
    publication
        .transform((data) => ({
            ...data,
            published_at: data.published_at
                ? new Date(data.published_at).toISOString()
                : null,
        }))
        .submit(publish(props.editor.id), { preserveScroll: true });
}
function withdraw(): void {
    if (!props.editor.id || !window.confirm(t('article.helper.unpublish')))
        return;
    publication.submit(unpublish(props.editor.id), { preserveScroll: true });
}
function upload(file: File, target: 'cover' | 'seo'): void {
    if (!props.editor.id || uploading.value) return;
    uploading.value = true;
    router.post(
        image(props.editor.id).url,
        { image: file },
        {
            preserveScroll: true,
            onSuccess: () => {
                const id = (page.props.flash as { image?: number } | undefined)
                    ?.image;
                if (id) {
                    if (target === 'cover')
                        form.cover = { media_id: id, alt: '' };
                    else form.seo.image = { media_id: id, alt: '' };
                }
            },
            onFinish: () => {
                uploading.value = false;
            },
        },
    );
}
const stopBefore = router.on('before', (event) => {
    if (
        event.detail.visit.method === 'get' &&
        dirty.value &&
        !window.confirm(t('website.helper.unsaved'))
    )
        return false;
});
function beforeUnload(event: BeforeUnloadEvent): void {
    if (dirty.value) {
        event.preventDefault();
        event.returnValue = '';
    }
}
onMounted(() => {
    mounted.value = true;
    window.addEventListener('beforeunload', beforeUnload);
});
onBeforeUnmount(() => {
    stopBefore();
    window.removeEventListener('beforeunload', beforeUnload);
});
watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [
            { title: t('breadcrumb.website'), href: websiteIndex() },
            { title: t('article.title.index'), href: index() },
        ],
    }),
);
</script>
<template>
    <Head
        :title="t(editor.id ? 'article.title.edit' : 'article.title.create')"
    />

    <PageContainer
        :title="t(editor.id ? 'article.title.edit' : 'article.title.create')"
    >
        <form
            class="flex w-full max-w-5xl flex-col gap-8"
            novalidate
            @submit.prevent="submit"
        >
            <div class="flex flex-wrap items-center gap-3">
                <StatusBadge
                    :label="
                        t(
                            editor.published
                                ? 'website.status.published'
                                : 'website.status.draft_only',
                        )
                    "
                    :tone="editor.published ? 'success' : 'neutral'"
                />
                <Button
                    v-if="canEdit"
                    type="submit"
                    :disabled="form.processing || uploading"
                >
                    {{ t('website.button.save_draft') }}
                </Button>
                <Button
                    v-if="editor.id"
                    as-child
                    variant="outline"
                >
                    <a
                        :href="preview(editor.id).url"
                        target="_blank"
                        rel="noopener"
                    >
                        {{ t('website.button.preview') }}
                    </a>
                </Button>
                <Button
                    v-if="canPublish && editor.id"
                    type="button"
                    :disabled="dirty || publication.processing || uploading"
                    @click="publishDraft"
                >
                    {{ t('website.button.publish') }}
                </Button>
                <Button
                    v-if="canPublish && editor.published"
                    type="button"
                    variant="outline"
                    :disabled="publication.processing"
                    @click="withdraw"
                >
                    {{ t('website.button.unpublish') }}
                </Button>
                <Button
                    as-child
                    variant="ghost"
                >
                    <Link :href="index()">{{ t('article.title.index') }}</Link>
                </Button>
            </div>
            <p class="text-sm text-muted-foreground">
                {{ t('article.helper.drafts') }}
            </p>
            <div
                v-if="
                    Object.keys(form.errors).length ||
                    Object.keys(publication.errors).length ||
                    page.props.errors.image
                "
                role="alert"
                class="rounded-lg border border-destructive p-4 text-sm text-destructive"
            >
                <p
                    v-for="(error, key) in {
                        ...form.errors,
                        ...publication.errors,
                        image: page.props.errors.image,
                    }"
                    :key="key"
                >
                    {{ error }}
                </p>
            </div>
            <FieldGroup>
                <Field
                    v-for="field in ['title', 'slug'] as const"
                    :key="field"
                    :data-invalid="!!form.errors[field]"
                >
                    <FieldLabel :for="`article-${field}`">
                        {{
                            t(
                                field === 'title'
                                    ? 'website.label.title'
                                    : 'article.label.slug',
                            )
                        }}
                    </FieldLabel>
                    <Input
                        :id="`article-${field}`"
                        v-model="form[field]"
                        :disabled="!canEdit"
                        :aria-invalid="!!form.errors[field]"
                        :maxlength="field === 'slug' ? 180 : 255"
                    />
                    <FieldError :errors="[form.errors[field]]" />
                </Field>
                <Field>
                    <FieldLabel for="article-excerpt">
                        {{ t('website.label.excerpt') }}
                    </FieldLabel>
                    <Textarea
                        id="article-excerpt"
                        v-model="form.excerpt"
                        :disabled="!canEdit"
                        maxlength="2000"
                    />
                </Field>
                <Field>
                    <FieldLabel for="article-author">
                        {{ t('article.label.author') }}
                    </FieldLabel>
                    <Select
                        v-model="form.author_id"
                        :disabled="!canEdit"
                    >
                        <SelectTrigger id="article-author">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem
                                    v-for="author in editor.authors"
                                    :key="author.value"
                                    :value="author.value"
                                >
                                    {{ author.label }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </Field>
                <Field orientation="horizontal">
                    <Switch
                        id="article-featured"
                        v-model="form.featured"
                        :disabled="!canEdit"
                    />
                    <FieldLabel for="article-featured">
                        {{ t('article.label.featured') }}
                    </FieldLabel>
                </Field>
            </FieldGroup>
            <section
                class="flex flex-col gap-3"
                aria-labelledby="article-body"
            >
                <h2
                    id="article-body"
                    class="text-lg font-semibold"
                >
                    {{ t('website.label.body') }}
                </h2>
                <RichTextEditor
                    v-if="mounted"
                    ref="bodyEditor"
                    v-model="form.body_json"
                    :disabled="!canEdit"
                    :error="form.errors.body_json"
                    @source-dirty="sourceDirty = $event"
                />
            </section>
            <WebsiteImageField
                :title="t('website.label.cover')"
                :value="form.cover"
                :media="editor.images"
                :disabled="!canEdit || uploading"
                :upload-disabled="!editor.id"
                :errors="{
                    alt: form.errors['cover.alt'],
                    media_id: form.errors['cover.media_id'],
                }"
                @update:value="form.cover = $event"
                @upload="upload($event, 'cover')"
            />
            <WebsiteSeoFields
                :value="form.seo"
                :image="form.seo.image"
                :media="editor.images"
                :disabled="!canEdit || uploading"
                :upload-disabled="!editor.id"
                :errors="form.errors"
                prefix="seo"
                with-image
                @update:value="form.seo = { ...$event, image: form.seo.image }"
                @update:image="form.seo.image = $event"
                @upload="upload($event, 'seo')"
            />
            <Field v-if="canPublish && editor.id">
                <FieldLabel for="article-date">
                    {{ t('article.label.date') }}
                </FieldLabel>
                <Input
                    id="article-date"
                    v-model="publication.published_at"
                    type="datetime-local"
                />
                <p class="text-sm text-muted-foreground">
                    {{ t('article.helper.date') }}
                </p>
            </Field>
        </form>
    </PageContainer>
</template>
