<script setup lang="ts">
import type { Editor } from '@tiptap/vue-3';
import { ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import { Field, FieldLabel, FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/composables/useTranslations';
import { applyEditorLink, normalizeEditorLink } from '@/lib/editor';
import type { EditorLinkOption } from '@/types';

import EditorApplicationLinkPicker from './EditorApplicationLinkPicker.vue';
const props = defineProps<{
    editor: Editor;
    open: boolean;
    options?: EditorLinkOption[];
}>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const { t } = useTranslations();

const href = ref('');
watch(
    () => props.open,
    (value) => {
        if (value) href.value = props.editor.getAttributes('link').href ?? '';
    },
);
function apply(): void {
    const url = normalizeEditorLink(href.value, window.location.href);

    const option = props.options?.find((option) => option.href === url);
    applyEditorLink(props.editor, url, option?.label ?? url);
    emit('update:open', false);
}
</script>
<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)"
        ><DialogContent
            ><DialogHeader
                ><DialogTitle>{{ t('editor.button.link') }}</DialogTitle
                ><DialogDescription>{{
                    t('editor.helper.link')
                }}</DialogDescription></DialogHeader
            >
            <form
                novalidate
                class="flex flex-col gap-4"
                @submit.prevent="apply"
            >
                <FieldGroup>
                    <Field v-if="options?.length"
                        ><FieldLabel for="editor-application-link">{{
                            t('editor.label.application_page')
                        }}</FieldLabel
                        ><EditorApplicationLinkPicker
                            v-model="href"
                            :options="options"
                    /></Field>
                    <Field
                        ><FieldLabel for="editor-link">{{
                            t('editor.label.url')
                        }}</FieldLabel
                        ><Input
                            id="editor-link"
                            v-model="href"
                            :placeholder="t('editor.placeholder.url')"
                    /></Field> </FieldGroup
                ><DialogFooter
                    ><Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                        >{{ t('common.button.cancel') }}</Button
                    ><Button type="submit">{{
                        t('editor.button.apply')
                    }}</Button></DialogFooter
                >
            </form>
        </DialogContent></Dialog
    >
</template>
