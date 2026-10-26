import type { EditorDocument } from './editor';
import type { PublicPageKind, PublicPreset } from './public';

export type WebsitePageKey = Extract<
    PublicPageKind,
    'home' | 'about' | 'contact' | 'article-index'
>;

export type WebsitePresetKey = PublicPreset;

export type WebsiteImageRef = {
    media_id: number;
    alt: string;
};

export type WebsiteMedia = {
    id: number;
    name: string;
    url: string;
};

export type WebsiteLink = {
    label: string;
    href: string;
};

export type WebsiteSeoForm = {
    title: string;
    description: string;
    robots: 'index,follow' | 'noindex,nofollow';
    image: WebsiteImageRef | null;
};

export type WebsiteSectionForm = { id: string; visible: boolean } & (
    | {
          type: 'hero';
          title: string;
          description: string;
          image: WebsiteImageRef | null;
          cta: WebsiteLink | null;
      }
    | { type: 'introduction'; title: string; body_json: EditorDocument }
    | {
          type: 'services' | 'strengths';
          title: string;
          items: { title: string; description: string }[];
      }
    | { type: 'articles'; title: string; featured: boolean; limit: number }
    | {
          type: 'contact';
          title: string;
          description: string;
          cta: WebsiteLink;
      }
);

export type WebsitePageContentForm = {
    title: string;
    excerpt: string;
    body_json: EditorDocument;
    sections: WebsiteSectionForm[];
    seo: WebsiteSeoForm;
    cover: WebsiteImageRef | null;
    version: number;
};

export type WebsitePageStatus = {
    draft_version: number;
    published: boolean;
    published_at: string | null;
};

export type WebsitePageOption = {
    page: WebsitePageKey;
    label: string;
    status: WebsitePageStatus | null;
};

export type WebsitePresetOption = {
    value: WebsitePresetKey;
    label: string;
};

export type WebsiteArea = {
    pages: WebsitePageOption[];
    preset: WebsitePresetKey;
    presetOptions: WebsitePresetOption[];
    publicPagesEnabled: boolean;
};

export type WebsitePageEditor = {
    page: { page: WebsitePageKey; label: string };
    content: WebsitePageContentForm | null;
    status: WebsitePageStatus | null;
    images: WebsiteMedia[];
};

export type WebsiteSettingsData = {
    public_pages_enabled: boolean;
    identity: string;
    seo: {
        title: string;
        description: string;
        robots: 'index,follow' | 'noindex,nofollow';
    };
    contact: {
        email: string | null;
        phone: string | null;
        address: string;
        hours: string;
        location_url: string | null;
        social_links: WebsiteLink[];
    };
    navigation: WebsiteLink[];
    footer: {
        text: string;
        links: WebsiteLink[];
    };
};

export type WebsiteSettingsProps = {
    settings: WebsiteSettingsData;
    preset: WebsitePresetKey;
    presetOptions: WebsitePresetOption[];
};

export type UpdateWebsitePageForm = WebsitePageContentForm;

export type UpdateWebsiteSettingsForm = {
    public_pages_enabled: boolean;
    identity: string;
    seo: {
        title: string;
        description: string;
        robots: 'index,follow' | 'noindex,nofollow';
    };
    contact: {
        email: string;
        phone: string;
        address: string;
        hours: string;
        location_url: string;
        social_links: WebsiteLink[];
    };
    navigation: WebsiteLink[];
    footer: {
        text: string;
        links: WebsiteLink[];
    };
};

export type ApplyWebsitePresetForm = {
    preset: WebsitePresetKey;
};
