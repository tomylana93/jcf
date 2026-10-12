import type { JSONContent } from '@tiptap/vue-3';

export type EditorDocument = JSONContent;

export type EditorLinkOption = { label: string; href: string };
export type EditorAction =
    | 'bold'
    | 'italic'
    | 'underline'
    | 'strike'
    | 'bulletList'
    | 'orderedList'
    | 'blockquote'
    | 'codeBlock'
    | 'horizontalRule'
    | 'undo'
    | 'redo'
    | 'clear'
    | EditorTableAction;

export type EditorTableAction =
    | 'insertTable'
    | 'addColumnBefore'
    | 'addColumnAfter'
    | 'deleteColumn'
    | 'addRowBefore'
    | 'addRowAfter'
    | 'deleteRow'
    | 'mergeCells'
    | 'splitCell'
    | 'toggleHeaderRow'
    | 'toggleHeaderColumn'
    | 'deleteTable';
