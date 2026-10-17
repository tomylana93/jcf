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
};

export type PublicContext = {
    canAccessDashboard: boolean;
    year: number;
};

export type PublicHomeProps = {
    canonicalUrl: string;
};
