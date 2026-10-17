<script setup lang="ts">
import {
    Head,
    Link,
    router,
    setLayoutProps,
    useForm,
    usePage,
} from '@inertiajs/vue3';
import { generateHTML } from '@tiptap/vue-3';
import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
    watchEffect,
} from 'vue';

import {
    destroy,
    store,
    update,
} from '@/actions/App/Http/Controllers/Documentation/ManageDocumentationController';
import RichTextContent from '@/components/editor/RichTextContent.vue';
import RichTextEditor from '@/components/editor/RichTextEditor.vue';
import PageContainer from '@/components/layout/PageContainer.vue';
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
import { Button, buttonVariants } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { Textarea } from '@/components/ui/textarea';
import { useTranslations } from '@/composables/useTranslations';
import { editorExtensions, emptyEditorDocument } from '@/lib/editor';
import { createEditorLinkOptions } from '@/lib/navigation';
import { create, edit, index } from '@/routes/documentation/manage';
import type { DocumentationForm, EditableDocumentation } from '@/types';

const props = defineProps<{ document: EditableDocumentation | null }>();

const { t } = useTranslations();
const page = usePage();

const linkOptions = computed(() =>
    createEditorLinkOptions(t, page.props.auth.permissions),
);

const form = useForm<DocumentationForm>({
    title: props.document?.title ?? '',
    slug: props.document?.slug ?? '',
    summary: props.document?.summary ?? '',
    content_json: props.document?.content_json ?? emptyEditorDocument(),
    status: props.document?.status ?? 'draft',
});

const editor = ref<InstanceType<typeof RichTextEditor> | null>(null);
const sourceDirty = ref(false);
const previewOpen = ref(false);
const deleteOpen = ref(false);

const deleting = useForm({});

const saving = ref(false);

const dirty = computed(() => form.isDirty || sourceDirty.value);

const preview = ref('');

watch(
    () => props.document,
    (document) => {
        if (document) {
            form.defaults({
                title: document.title,
                slug: document.slug,
                summary: document.summary ?? '',
                content_json: document.content_json,
                status: document.status,
            });
            form.reset();
        }
    },
);

function submit(): void {
    if (!editor.value?.prepareSave()) return;
    saving.value = true;
    form.submit(props.document ? update(props.document.id) : store(), {
        onSuccess: () => form.defaults(),
        onFinish: () => {
            saving.value = false;
        },
    });
}

function openPreview(): void {
    if (editor.value?.prepareSave()) {
        preview.value = generateHTML(form.content_json, editorExtensions());
        previewOpen.value = true;
    }
}

function remove(): void {
    if (props.document)
        deleting.submit(destroy(props.document.id), {
            onBefore: () => {
                saving.value = true;
            },
            onFinish: () => {
                saving.value = false;
            },
        });
}

const stopBefore = router.on('before', (event) => {
    if (
        event.detail.visit.method === 'get' &&
        dirty.value &&
        !saving.value &&
        !window.confirm(t('documentation.helper.unsaved'))
    )
        return false;
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
            { title: t('breadcrumb.documentation'), href: index() },
            {
                title: props.document
                    ? t('documentation.card.edit_title')
                    : t('documentation.card.create_title'),
                href: props.document ? edit(props.document.id) : create(),
            },
        ],
    }),
);
</script>
<template>
    <Head :title="t('documentation.title')" />

    <PageContainer
        :title="
            document
                ? t('documentation.card.edit_title')
                : t('documentation.card.create_title')
        "
    >
        <form
            novalidate
            class="flex w-full min-w-0 flex-col gap-6"
            @submit.prevent="submit"
        >
            <FieldGroup class="grid gap-4 md:grid-cols-2">
                <Field :data-invalid="!!form.errors.title">
                    <FieldLabel for="document-title">
                        {{ t('documentation.label.title') }}
                    </FieldLabel>
                    <Input
                        id="document-title"
                        v-model="form.title"
                        :aria-invalid="!!form.errors.title"
                        :aria-describedby="
                            form.errors.title
                                ? 'document-title-error'
                                : undefined
                        "
                        :disabled="form.processing"
                    />
                    <FieldError
                        id="document-title-error"
                        :errors="[form.errors.title]"
                    />
                </Field>
                <Field :data-invalid="!!form.errors.slug">
                    <FieldLabel for="document-slug">
                        {{ t('documentation.label.slug') }}
                    </FieldLabel>
                    <Input
                        id="document-slug"
                        v-model="form.slug"
                        :placeholder="t('documentation.placeholder.slug')"
                        :aria-invalid="!!form.errors.slug"
                        :aria-describedby="
                            form.errors.slug ? 'document-slug-error' : undefined
                        "
                        :disabled="form.processing"
                    />
                    <FieldError
                        id="document-slug-error"
                        :errors="[form.errors.slug]"
                    />
                </Field>
                <Field
                    :data-invalid="!!form.errors.summary"
                    class="md:col-span-2"
                >
                    <FieldLabel for="document-summary">
                        {{ t('documentation.label.summary') }}
                    </FieldLabel>
                    <Textarea
                        id="document-summary"
                        v-model="form.summary"
                        :aria-invalid="!!form.errors.summary"
                        :aria-describedby="
                            form.errors.summary
                                ? 'document-summary-error'
                                : undefined
                        "
                        :disabled="form.processing"
                    />
                    <FieldError
                        id="document-summary-error"
                        :errors="[form.errors.summary]"
                    />
                </Field>
            </FieldGroup>
            <Field :data-invalid="!!form.errors.content_json">
                <FieldLabel>
                    {{ t('documentation.label.content') }}
                </FieldLabel>
                <RichTextEditor
                    ref="editor"
                    v-model="form.content_json"
                    :disabled="form.processing"
                    :error="form.errors.content_json"
                    :link-options="linkOptions"
                    @source-dirty="sourceDirty = $event"
                />
                <FieldError :errors="[form.errors.content_json]" />
            </Field>
            <Field
                class="max-w-xs"
                :data-invalid="!!form.errors.status"
            >
                <FieldLabel for="document-status">
                    {{ t('documentation.label.status') }}
                </FieldLabel>
                <Select
                    v-model="form.status"
                    :disabled="form.processing"
                >
                    <SelectTrigger id="document-status">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectItem value="draft">
                                {{ t('documentation.status.draft') }}
                            </SelectItem>
                            <SelectItem value="published">
                                {{ t('documentation.status.published') }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
                <FieldError :errors="[form.errors.status]" />
            </Field>
            <div class="flex flex-wrap gap-2">
                <Button
                    type="submit"
                    :disabled="form.processing"
                    data-test="save-documentation"
                >
                    {{ t('common.button.save') }}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    @click="openPreview"
                >
                    {{ t('documentation.button.preview') }}
                </Button>
                <Link
                    :href="index()"
                    :class="buttonVariants({ variant: 'outline' })"
                >
                    {{ t('common.button.cancel') }}
                </Link>
                <Button
                    v-if="document"
                    type="button"
                    variant="destructive"
                    :disabled="deleting.processing"
                    @click="deleteOpen = true"
                >
                    {{ t('documentation.button.delete') }}
                </Button>
            </div>
        </form>
        <Dialog v-model:open="previewOpen">
            <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-4xl">
                <DialogHeader>
                    <DialogTitle>
                        {{ form.title || t('documentation.button.preview') }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ form.summary }}
                    </DialogDescription>
                </DialogHeader>
                <RichTextContent :html="preview" />
            </DialogContent>
        </Dialog>
        <AlertDialog v-model:open="deleteOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ t('documentation.button.delete') }}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {{ t('documentation.helper.delete') }}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>
                        {{ t('common.button.cancel') }}
                    </AlertDialogCancel>
                    <AlertDialogAction @click="remove">
                        {{ t('documentation.button.delete') }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </PageContainer>
</template>
