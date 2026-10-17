export type PublicPreset = 'corporate' | 'editorial' | 'minimal';
export type PublicPageKind =
    | 'home'
    | 'about'
    | 'contact'
    | 'article-index'
    | 'article';

export type PublicImage = {
    url: string;
    alt: string;
};

export type PublicSeo = {
    title: string;
    description: string;
    canonicalUrl: string;
    robots: 'index,follow' | 'noindex,nofollow';
    locale: string;
    image: PublicImage | null;
};

export type PublicNavigationItem = {
    label: string;
    href: string;
    active: boolean;
};

export type PublicSection = {
    id: string;
    visible: boolean;
} & (
    | {
          type: 'hero';
          title: string;
          description: string;
          image: PublicImage | null;
          cta: PublicNavigationItem | null;
      }
    | { type: 'introduction'; title: string; body: string }
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
          cta: PublicNavigationItem;
      }
);

export type PublicPage = {
    kind: PublicPageKind;
    title: string;
    seo: PublicSeo;
    sections: PublicSection[];
    body?: string;
    cover?: PublicImage | null;
    article?: { author: string; publishedLabel: string; publishedAt: string };
};

export type PublicPresetPreferences = {
    sectionOrder: string[];
    hiddenSections: string[];
};

export type PublicWebsite = {
    preset: PublicPreset;
    preferences: Record<PublicPreset, PublicPresetPreferences>;
    navigation: PublicNavigationItem[];
    page: PublicPage;
    breadcrumbs?: PublicNavigationItem[];
    articles?: PublicArticle[];
    pagination?: PublicNavigationItem[];
    contact?: PublicContact;
    footer?: { text: string; links: PublicNavigationItem[] };
    /** Present on authenticated previews only. */
    preview?: { mode: 'draft' | 'published' | 'empty' };
};

export type PublicArticle = {
    id: string;
    title: string;
    excerpt: string;
    href: string;
    cover: PublicImage | null;
    featured: boolean;
    publishedAt: string;
    publishedLabel: string;
};

export type PublicContact = {
    email: string | null;
    phone: string | null;
    address: string;
    hours: string;
    location_url: string | null;
    social_links: { label: string; href: string }[];
};

export type PublicContext = {
    canAccessDashboard: boolean;
    year: number;
};

export type PublicHomeProps = {
    canonicalUrl: string;
    website?: PublicWebsite;
};
