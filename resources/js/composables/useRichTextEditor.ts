import { useEditor } from '@tiptap/vue-3';
import { watch } from 'vue';

import { editorExtensions } from '@/lib/editor';
import type { EditorDocument } from '@/types';

export function useRichTextEditor(
    props: { modelValue: EditorDocument; disabled?: boolean },
    emit: (value: EditorDocument) => void,
    label: string,
) {
    const editor = useEditor({
        extensions: editorExtensions(),
        content: props.modelValue,
        editable: !props.disabled,
        enableContentCheck: true,
        editorProps: {
            attributes: {
                class: 'editor-content min-h-80 p-4 outline-none',
                role: 'textbox',
                'aria-multiline': 'true',
                'aria-label': label,
            },
        },
        onUpdate: ({ editor }) => emit(editor.getJSON()),
    });
    watch(
        () => props.modelValue,
        (value) => {
            if (
                editor.value &&
                JSON.stringify(value) !== JSON.stringify(editor.value.getJSON())
            ) {
                editor.value.commands.setContent(value, {
                    emitUpdate: false,
                    errorOnInvalidContent: true,
                });
            }
        },
    );
    watch(
        () => props.disabled,
        (value) => editor.value?.setEditable(!value),
    );
    return editor;
}
