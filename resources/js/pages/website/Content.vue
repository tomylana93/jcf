<script setup lang="ts">
import {
    Head,
    router,
    setLayoutProps,
    useForm,
    usePage,
} from '@inertiajs/vue3';
import { ExternalLink, EyeOff, Plus, SendHorizonal } from '@lucide/vue';
import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
    watchEffect,
} from 'vue';

import {
    publish,
    saveDraft,
    storeImage,
    unpublish,
} from '@/actions/App/Http/Controllers/Website/WebsitePageController';
import RichTextEditor from '@/components/editor/RichTextEditor.vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import WebsiteImageField from '@/components/website/WebsiteImageField.vue';
import WebsiteSectionEditor from '@/components/website/WebsiteSectionEditor.vue';
import WebsiteSeoFields from '@/components/website/WebsiteSeoFields.vue';
import { useTranslations } from '@/composables/useTranslations';
import { emptyEditorDocument } from '@/lib/editor';
import { createEditorLinkOptions } from '@/lib/navigation';
import { preview, index as websiteIndex } from '@/routes/website';
import { edit as contentEdit } from '@/routes/website/page';
import type {
    EditorLinkOption,
    UpdateWebsitePageForm,
    WebsitePageEditor,
    WebsitePageKey,
    WebsiteSectionForm,
} from '@/types';

const props = defineProps<{
    editor: WebsitePageEditor;
}>();

const { t } = useTranslations();

const page = usePage();

type UploadTarget =
    | { kind: 'cover' }
    | { kind: 'seo' }
    | { kind: 'section'; index: number };

const canEdit = page.props.auth.permissions.includes('website.content.edit');

const canPublish = page.props.auth.permissions.includes(
    'website.content.publish',
);

const linkOptions = computed<EditorLinkOption[]>(() =>
    createEditorLinkOptions(t, page.props.auth.permissions),
);

function formValues(editor: WebsitePageEditor): UpdateWebsitePageForm {
    return {
        title: editor.content?.title ?? '',
        excerpt: editor.content?.excerpt ?? '',
        body_json: editor.content?.body_json ?? emptyEditorDocument(),
        sections: editor.content?.sections ?? [],
        seo: editor.content?.seo ?? {
            title: '',
            description: '',
            robots: 'index,follow',
            image: null,
        },
        cover: editor.content?.cover ?? null,
        version: editor.content?.version ?? 0,
    };
}

const form = useForm<UpdateWebsitePageForm>(formValues(props.editor));

const bodyEditor = ref<InstanceType<typeof RichTextEditor> | null>(null);

const sectionEditors = ref<
    Record<number, InstanceType<typeof WebsiteSectionEditor> | null>
>({});

const uploadTarget = ref<UploadTarget | null>(null);

const publishOpen = ref(false);

const unpublishOpen = ref(false);

const actionForm = useForm({ version: props.editor.content?.version ?? 0 });

const saving = ref(false);
const uploading = ref(false);

const dirty = computed(() => form.isDirty);

const staleVersion = computed(
    () => form.errors.version ?? actionForm.errors.version,
);

const statusLabel = computed(() => {
    if (props.editor.status?.published) {
        return t('website.status.published');
    }

    return props.editor.content === null
        ? t('website.status.not_started')
        : t('website.status.draft_only');
});

const previewHref = computed(
    () => preview({ page: props.editor.page.page }).url,
);

const sectionTypes = [
    'hero',
    'introduction',
    'services',
    'strengths',
    'articles',
    'contact',
] as const;

/**
 * Re-initialize the form when the server side draft actually advanced:
 * a page switch or a new saved version, not when publication state changes.
 */
let initializedPage: WebsitePageKey | null = props.editor.page.page;

let initializedVersion: number | undefined = props.editor.content?.version;

watch(
    () => [props.editor.page.page, props.editor.content?.version] as const,
    ([pageKey, version]) => {
        if (pageKey !== initializedPage || version !== initializedVersion) {
            initializedPage = pageKey;
            initializedVersion = version;
            form.defaults(formValues(props.editor));
            form.reset();
        } else {
            form.version = version ?? form.version;
        }
    },
);

/**
 * Flush pending HTML source editors, then submit the working draft.
 */
function submit(): void {
    if (!canEdit || saving.value || uploading.value) return;
    if (!bodyEditor.value?.prepareSave()) return;
    for (const editor of Object.values(sectionEditors.value)) {
        if (editor && !editor.prepareSave()) return;
    }
    saving.value = true;
    form.submit(saveDraft({ page: props.editor.page.page }), {
        preserveScroll: true,
        onSuccess: () => form.defaults(),
        onFinish: () => {
            saving.value = false;
        },
    });
}

function publishDraft(): void {
    actionForm.transform(() => ({
        version: props.editor.content?.version ?? 0,
    }));
    actionForm.submit(publish({ page: props.editor.page.page }), {
        preserveScroll: true,
    });
}

function withdraw(): void {
    actionForm.submit(unpublish({ page: props.editor.page.page }), {
        preserveScroll: true,
    });
}

/**
 * The image upload response flashes the new media id; attach it to the field
 * that started the upload.
 */
function refreshUploadTarget(): void {
    const flash = page.flash as { image?: number } | undefined;
    const mediaId = flash?.image;
    const target = uploadTarget.value;
    if (mediaId === undefined || target === null) return;
    if (target.kind === 'cover') {
        form.cover = { media_id: mediaId, alt: '' };
    } else if (target.kind === 'seo') {
        form.seo.image = { media_id: mediaId, alt: '' };
    } else {
        const section = form.sections[target.index];
        if (section?.type === 'hero') {
            form.sections = form.sections.map((item, at) =>
                at === target.index
                    ? { ...item, image: { media_id: mediaId, alt: '' } }
                    : item,
            );
        }
    }
    uploadTarget.value = null;
}

function uploadImage(file: File, target?: UploadTarget): void {
    if (!canEdit || props.editor.content === null || uploading.value) return;
    uploading.value = true;
    if (target) {
        uploadTarget.value = target;
    }
    router.post(
        storeImage({ page: props.editor.page.page }).url,
        { image: file },
        {
            preserveScroll: true,
            onSuccess: () => refreshUploadTarget(),
            onFinish: () => {
                uploadTarget.value = null;
                uploading.value = false;
            },
        },
    );
}

function uploadFromSection(index: number, file: File): void {
    uploadImage(file, { kind: 'section', index });
}

function addSection(type: WebsiteSectionForm['type']): void {
    const base = {
        id: `${type}-${Date.now().toString(36)}`,
        visible: true,
        title: '',
    };
    const created: WebsiteSectionForm =
        type === 'hero'
            ? {
                  ...base,
                  type: 'hero',
                  description: '',
                  image: null,
                  cta: null,
              }
            : type === 'introduction'
              ? {
                    ...base,
                    type: 'introduction',
                    body_json: emptyEditorDocument(),
                }
              : type === 'articles'
                ? { ...base, type: 'articles', featured: false, limit: 6 }
                : type === 'contact'
                  ? {
                        ...base,
                        type: 'contact',
                        description: '',
                        cta: { label: '', href: '' },
                    }
                  : { ...base, type, items: [] };
    form.sections = [...form.sections, created];
}

function patchSection(index: number, section: WebsiteSectionForm): void {
    form.sections = form.sections.map((item, at) =>
        at === index ? section : item,
    );
}

function moveSection(index: number, direction: -1 | 1): void {
    const target = index + direction;
    if (target < 0 || target >= form.sections.length) return;
    const sections = [...form.sections];
    const [moved] = sections.splice(index, 1);
    if (moved) {
        sections.splice(target, 0, moved);
        sectionEditors.value = {};
        form.sections = sections;
    }
}

function removeSection(index: number): void {
    form.sections = form.sections.filter((_, at) => at !== index);
    sectionEditors.value = {};
}

const stopBefore = router.on('before', (event) => {
    if (
        event.detail.visit.method === 'get' &&
        dirty.value &&
        !saving.value &&
        !window.confirm(t('website.helper.unsaved'))
    ) {
        return false;
    }
});

function beforeUnload(event: BeforeUnloadEvent): void {
    if (dirty.value && !saving.value) {
        event.preventDefault();
        event.returnValue = '';
    }
}

onMounted(() => window.addEventListener('beforeunload', beforeUnload));

onBeforeUnmount(() => {
    stopBefore();
    window.removeEventListener('beforeunload', beforeUnload);
});

watchEffect(() =>
    setLayoutProps({
        breadcrumbs: [
            { title: t('breadcrumb.website'), href: websiteIndex() },
            {
                title: props.editor.page.label,
                href: contentEdit({ page: props.editor.page.page }),
            },
        ],
    }),
);
</script>

<template>
    <Head
        :title="t('website.title.content', { label: props.editor.page.label })"
    />

    <PageContainer :title="props.editor.page.label">
        <form
            novalidate
            class="flex w-full flex-col gap-6"
            @submit.prevent="submit"
        >
            <Card>
                <CardHeader>
                    <div
                        class="flex flex-wrap items-center justify-between gap-4"
                    >
                        <div class="flex flex-wrap items-center gap-3">
                            <StatusBadge
                                :label="statusLabel"
                                :tone="
                                    props.editor.status?.published
                                        ? 'success'
                                        : 'neutral'
                                "
                                data-test="website_editor_status"
                            />
                            <span
                                v-if="props.editor.content"
                                class="text-sm text-muted-foreground"
                                data-test="website_editor_version"
                            >
                                {{
                                    t('website.helper.draft_saved', {
                                        version: props.editor.content.version,
                                    })
                                }}
                            </span>
                            <span
                                v-if="props.editor.content && dirty"
                                class="text-xs font-medium text-muted-foreground"
                                data-test="website_editor_dirty"
                            >
                                {{ t('website.helper.unsaved_changes') }}
                            </span>
                            <span
                                v-if="props.editor.status?.published"
                                class="text-sm text-muted-foreground"
                            >
                                {{ t('website.label.published_at') }}
                                {{
                                    new Date(
                                        props.editor.status.published_at ?? '',
                                    ).toLocaleString()
                                }}
                                · {{ t('website.helper.unpublished_note') }}
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                as-child
                                data-test="preview_website"
                            >
                                <a
                                    :href="previewHref"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    <ExternalLink class="size-4" />
                                    {{ t('website.button.preview') }}
                                </a>
                            </Button>
                            <Button
                                v-if="
                                    canPublish && props.editor.status?.published
                                "
                                type="button"
                                size="sm"
                                variant="outline"
                                :disabled="actionForm.processing"
                                data-test="request_unpublish_website"
                                @click="unpublishOpen = true"
                            >
                                <EyeOff class="size-4" />
                                {{ t('website.button.unpublish') }}
                            </Button>
                            <Button
                                v-if="
                                    canPublish && props.editor.content !== null
                                "
                                type="button"
                                size="sm"
                                :disabled="
                                    form.isDirty ||
                                    actionForm.processing ||
                                    uploading
                                "
                                data-test="request_publish_website"
                                @click="publishOpen = true"
                            >
                                <SendHorizonal class="size-4" />
                                {{ t('website.button.publish') }}
                            </Button>
                            <Button
                                v-if="canEdit"
                                type="submit"
                                size="sm"
                                :disabled="saving || uploading"
                                data-test="save_website_draft"
                            >
                                {{ t('website.button.save_draft') }}
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="flex flex-col gap-6">
                    <FieldError
                        v-if="staleVersion"
                        :errors="[staleVersion] as string[]"
                    />
                    <Field
                        v-if="
                            form.isDirty &&
                            canPublish &&
                            props.editor.status &&
                            props.editor.content !== null
                        "
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('website.helper.publish_state') }}
                    </Field>
                    <Field :data-invalid="!!form.errors.title">
                        <FieldLabel for="website_page_title">
                            {{ t('website.label.title') }}
                        </FieldLabel>
                        <Input
                            id="website_page_title"
                            v-model="form.title"
                            type="text"
                            :disabled="!canEdit || uploading"
                            :aria-invalid="!!form.errors.title"
                            data-test="website_page_title"
                        />
                        <FieldError
                            v-if="form.errors.title"
                            :errors="[form.errors.title] as string[]"
                        />
                    </Field>
                    <Field :data-invalid="!!form.errors.excerpt">
                        <FieldLabel for="website_page_excerpt">
                            {{ t('website.label.excerpt') }}
                        </FieldLabel>
                        <Textarea
                            id="website_page_excerpt"
                            v-model="form.excerpt"
                            :rows="3"
                            :disabled="!canEdit || uploading"
                            :aria-invalid="!!form.errors.excerpt"
                            data-test="website_page_excerpt"
                        />
                        <FieldError
                            v-if="form.errors.excerpt"
                            :errors="[form.errors.excerpt] as string[]"
                        />
                    </Field>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <div class="flex flex-col">
                            <CardTitle>
                                {{ t('website.label.sections') }}
                            </CardTitle>
                            <CardDescription>
                                {{ t('website.helper.section.hidden') }}
                            </CardDescription>
                        </div>
                        <DropdownMenu v-if="canEdit">
                            <DropdownMenuTrigger as-child>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    data-test="add_website_section"
                                >
                                    <Plus class="size-4" />
                                    {{ t('website.button.add_section') }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent>
                                <DropdownMenuGroup>
                                    <DropdownMenuItem
                                        v-for="type in sectionTypes"
                                        :key="type"
                                        :data-test="`add_website_section_${type}`"
                                        @click="addSection(type)"
                                    >
                                        {{
                                            t(
                                                `website.label.section.type.${type}`,
                                            )
                                        }}
                                    </DropdownMenuItem>
                                </DropdownMenuGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </CardHeader>
                <CardContent class="flex flex-col gap-4">
                    <p
                        v-if="form.sections.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('website.helper.no_links') }}
                    </p>
                    <div
                        v-for="(section, index) in form.sections"
                        :key="index"
                    >
                        <WebsiteSectionEditor
                            :ref="
                                (element) => {
                                    sectionEditors[index] =
                                        element as InstanceType<
                                            typeof WebsiteSectionEditor
                                        >;
                                }
                            "
                            :section="section"
                            :index="index"
                            :media="props.editor.images"
                            :disabled="!canEdit || uploading"
                            :upload-disabled="props.editor.content === null"
                            :errors="form.errors"
                            :link-options="linkOptions"
                            :is-first="index === 0"
                            :is-last="index === form.sections.length - 1"
                            @update:section="patchSection(index, $event)"
                            @move="moveSection(index, $event)"
                            @remove="removeSection(index)"
                            @upload="uploadFromSection(index, $event)"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('website.label.body') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <RichTextEditor
                        ref="bodyEditor"
                        :model-value="form.body_json"
                        :disabled="!canEdit || uploading"
                        :error="form.errors.body_json"
                        :link-options="linkOptions"
                        @update:model-value="form.body_json = $event"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('website.label.seo.title') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <WebsiteSeoFields
                        :value="form.seo"
                        :image="form.seo.image"
                        :disabled="!canEdit || uploading"
                        :media="props.editor.images"
                        :upload-disabled="props.editor.content === null"
                        prefix="seo"
                        with-image
                        :errors="form.errors"
                        @update:value="
                            form.seo = { ...$event, image: form.seo.image }
                        "
                        @update:image="form.seo.image = $event"
                        @upload="uploadImage($event, { kind: 'seo' })"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('website.label.cover') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <WebsiteImageField
                        :title="t('website.label.cover')"
                        :value="form.cover"
                        :media="props.editor.images"
                        :disabled="!canEdit || uploading"
                        :upload-disabled="props.editor.content === null"
                        :errors="{
                            media_id: form.errors['cover.media_id'],
                            alt: form.errors['cover.alt'],
                        }"
                        @update:value="form.cover = $event"
                        @upload="uploadImage($event, { kind: 'cover' })"
                    />
                </CardContent>
            </Card>
        </form>

        <AlertDialog v-model:open="publishOpen">
            <AlertDialogContent class="max-w-md">
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ t('website.button.publish') }}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {{ t('website.helper.publish_state') }}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>
                        {{ t('common.button.cancel') }}
                    </AlertDialogCancel>
                    <AlertDialogAction
                        data-test="confirm_publish_website"
                        @click="publishDraft()"
                    >
                        {{ t('website.button.publish') }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog v-model:open="unpublishOpen">
            <AlertDialogContent class="max-w-md">
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ t('website.button.unpublish') }}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {{ t('website.status.unpublished_toast') }}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>
                        {{ t('common.button.cancel') }}
                    </AlertDialogCancel>
                    <AlertDialogAction
                        data-test="confirm_unpublish_website"
                        @click="withdraw()"
                    >
                        {{ t('website.button.unpublish') }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </PageContainer>
</template>
