import type { EditorDocument, TablePaginator } from '@/types';

/** Mirrors App\Enums\DocumentationStatus; tests/Feature/Enums/FrontendEnumMirrorTest.php keeps it in sync. */
export type DocumentationStatus = 'draft' | 'published';

export type DocumentationRecord = {
    id: string;
    title: string;
    slug: string;
    summary: string | null;
    status: DocumentationStatus;
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
