import { Table, TableKit } from '@tiptap/extension-table';
import StarterKit from '@tiptap/starter-kit';
import type { Editor } from '@tiptap/vue-3';

import type {
    EditorAction,
    EditorDocument,
    EditorTableAction,
    TranslationKey,
} from '@/types';

export const emptyEditorDocument = (): EditorDocument => ({
    type: 'doc',
    content: [{ type: 'paragraph' }],
});
export const editorExtensions = () => [
    StarterKit.configure({
        heading: { levels: [2, 3, 4] },
        trailingNode: false,
        link: {
            openOnClick: false,
            autolink: false,
            protocols: ['http', 'https', 'mailto'],
        },
    }),
    TableKit.configure({ table: false }),
    Table.extend({
        renderHTML() {
            return ['table', ['tbody', 0]];
        },
    }),
];

export const editorTableActions: {
    action: EditorTableAction;
    label: TranslationKey;
}[] = [
    { action: 'insertTable', label: 'editor.button.insert_table' },
    { action: 'addColumnBefore', label: 'editor.button.column_before' },
    { action: 'addColumnAfter', label: 'editor.button.column_after' },
    { action: 'deleteColumn', label: 'editor.button.delete_column' },
    { action: 'addRowBefore', label: 'editor.button.row_before' },
    { action: 'addRowAfter', label: 'editor.button.row_after' },
    { action: 'deleteRow', label: 'editor.button.delete_row' },
    { action: 'mergeCells', label: 'editor.button.merge_cells' },
    { action: 'splitCell', label: 'editor.button.split_cell' },
    { action: 'toggleHeaderRow', label: 'editor.button.header_row' },
    { action: 'toggleHeaderColumn', label: 'editor.button.header_column' },
    { action: 'deleteTable', label: 'editor.button.delete_table' },
];

export function canRunEditorTableAction(
    editor: Editor,
    action: EditorTableAction,
): boolean {
    if (!editor.isEditable) return false;
    if (action === 'insertTable')
        return (
            !editor.isActive('table') &&
            editor.can().insertTable({ rows: 3, cols: 3, withHeaderRow: true })
        );
    return editor.isActive('table') && editor.can()[action]();
}

export function normalizeEditorLink(value: string, baseUrl: string): string {
    const href = value.trim();
    if (!href || href.startsWith('#') || href.startsWith('//')) return href;
    try {
        const base = new URL(baseUrl);

        const url = new URL(
            href,
            href.startsWith('?') ? base : base.origin + '/',
        );
        return url.origin === base.origin
            ? url.pathname + url.search + url.hash
            : href;
    } catch {
        return href;
    }
}

export function applyEditorLink(
    editor: Editor,
    href: string,
    label = href,
): void {
    if (!editor.isEditable) return;
    const chain = editor.chain().focus().extendMarkRange('link');
    if (!href) {
        chain.unsetLink().run();
        return;
    }
    const attrs = {
        href,
        target:
            href.startsWith('/') || href.startsWith('#') ? '_self' : '_blank',
    };
    if (editor.state.selection.empty && !editor.isActive('link')) {
        chain
            .insertContent({
                type: 'text',
                text: label,
                marks: [{ type: 'link', attrs }],
            })
            .run();
    } else {
        chain.setLink(attrs).run();
    }
}

export function runEditorAction(editor: Editor, action: EditorAction): void {
    if (!editor.isEditable) return;
    const chain = editor.chain().focus();
    switch (action) {
        case 'bold':
            chain.toggleBold().run();
            break;
        case 'italic':
            chain.toggleItalic().run();
            break;
        case 'underline':
            chain.toggleUnderline().run();
            break;
        case 'strike':
            chain.toggleStrike().run();
            break;
        case 'bulletList':
            chain.toggleBulletList().run();
            break;
        case 'orderedList':
            chain.toggleOrderedList().run();
            break;
        case 'blockquote':
            chain.toggleBlockquote().run();
            break;
        case 'codeBlock':
            chain.toggleCodeBlock().run();
            break;
        case 'horizontalRule':
            chain.setHorizontalRule().run();
            break;
        case 'undo':
            chain.undo().run();
            break;
        case 'redo':
            chain.redo().run();
            break;
        case 'clear':
            chain.unsetAllMarks().clearNodes().run();
            break;
        default:
            if (canRunEditorTableAction(editor, action)) {
                if (action === 'insertTable')
                    chain
                        .insertTable({ rows: 3, cols: 3, withHeaderRow: true })
                        .run();
                else chain[action]().run();
            }
    }
}

export function inspectEditorHtml(source: string): boolean {
    const root = new DOMParser().parseFromString(source, 'text/html');

    const tags = new Set([
        'P',
        'H2',
        'H3',
        'H4',
        'STRONG',
        'B',
        'EM',
        'I',
        'U',
        'S',
        'DEL',
        'STRIKE',
        'CODE',
        'PRE',
        'UL',
        'OL',
        'LI',
        'BLOCKQUOTE',
        'BR',
        'HR',
        'A',
        'TABLE',
        'THEAD',
        'TBODY',
        'TFOOT',
        'TR',
        'TD',
        'TH',
    ]);
    return (
        Array.from(root.body.querySelectorAll('*')).some((element) => {
            if (!tags.has(element.tagName)) return true;
            return Array.from(element.attributes).some((attribute) => {
                const allowed =
                    element.tagName === 'A'
                        ? ['href', 'target', 'rel', 'title']
                        : element.tagName === 'OL'
                          ? ['start']
                          : ['TD', 'TH'].includes(element.tagName)
                            ? ['colspan', 'rowspan', 'colwidth', 'style']
                            : [];
                if (attribute.name === 'style')
                    return !/^text-align:\s*(left|right|center);?\s*$/.test(
                        attribute.value,
                    );
                return !allowed.includes(attribute.name);
            });
        }) || /<!doctype|<\/?(?:html|head|body)\b/i.test(source)
    );
}
