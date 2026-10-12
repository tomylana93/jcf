import type { EditorDocument, TablePaginator } from '@/types';

export type DocumentationRecord = {
    id: string;
    title: string;
    slug: string;
    summary: string | null;
    status: 'draft' | 'published';
};

export type EditableDocumentation = DocumentationRecord & {
    content_json: EditorDocument;
};
export type ReadableDocumentation = DocumentationRecord & {
    content_html: string;
};
export type DocumentationPaginator = TablePaginator<DocumentationRecord> & {
    last_page: number;
};
