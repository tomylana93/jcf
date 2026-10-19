import { createSSRApp, h } from 'vue';

import { publicPresets } from '@/components/public/presets';
import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicPageContent from '@/components/public/PublicPageContent.vue';
import { createTranslationPlugin } from '@/lib/i18n';
import type { PublicWebsite } from '@/types/public';

export function createFixture(website: PublicWebsite, locale: string) {
    const app = createSSRApp({
        render: () =>
            h('main', { class: 'py-16' }, [
                h(
                    PublicContainer,
                    { width: publicPresets[website.preset].width },
                    () => h(PublicPageContent, { website }),
                ),
            ]),
    });
    app.use(createTranslationPlugin(locale, 'en'));
    return app;
}
