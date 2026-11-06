import type { EditorDocument, TablePaginator } from '@/types';

/** Mirrors App\Enums\DocumentationStatus; tests/Feature/Enums/FrontendEnumMirrorTest.php keeps it in sync. */
export type DocumentationStatus = 'draft' | 'published';

/** Mirrors App\Http\Resources\Documentation\DocumentationResource. */
export type DocumentationRecord = {
    id: string;
    title: string;
    slug: string;
    summary: string | null;
    status: DocumentationStatus;
};

/** Mirrors DocumentationResource::editable(). */
export type EditableDocumentation = DocumentationRecord & {
    content_json: EditorDocument;
};
/** Mirrors DocumentationResource::readable(). */
export type ReadableDocumentation = DocumentationRecord & {
    content_html: string;
};
export type DocumentationPaginator = TablePaginator<DocumentationRecord> & {
    last_page: number;
};
