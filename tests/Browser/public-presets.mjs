// Existing Vue/Vite/Playwright tooling; no production fixture route or new test dependency.
import assert from 'node:assert/strict';
import { mkdir } from 'node:fs/promises';
import { createServer as createHttpServer } from 'node:http';
import { resolve } from 'node:path';

import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { renderToString } from '@vue/server-renderer';
import { chromium } from 'playwright';
import { createServer } from 'vite';

const vite = await createServer({
    configFile: false,
    plugins: [vue(), tailwindcss()],
    resolve: { alias: { '@': resolve('resources/js') } },
    server: { middlewareMode: true },
    appType: 'custom',
});
const fixture = await vite.ssrLoadModule(
    '/tests/Browser/Fixtures/public-preset.ts',
);
const { composePublicSections, publicPresets } = await vite.ssrLoadModule(
    '/resources/js/components/public/presets.ts',
);
const image = {
    url: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jB1sAAAAASUVORK5CYII=',
    alt: 'Fixture image',
};
function website(preset, kind, state) {
    const long = state === 'long';
    const text = long
        ? 'A meaningful publication about services and organizational activities. '.repeat(
              16,
          ) + 'x'.repeat(180)
        : 'Content supplied for the component regression fixture.';
    const sections =
        state === 'empty'
            ? []
            : [
                  {
                      id: 'hero',
                      type: 'hero',
                      visible: true,
                      title: `Public ${kind} ${long ? text : 'fixture'}`,
                      description: text,
                      image: state === 'typical' ? image : null,
                      cta: {
                          label: 'Contact information',
                          href: '#contact',
                          active: false,
                      },
                  },
                  {
                      id: 'intro',
                      type: 'introduction',
                      visible: true,
                      title: 'Introduction',
                      body: `<p>${text}</p><h3>Readable details</h3><p>Sanitized <strong>rich text</strong>.</p><pre><code>${'code'.repeat(90)}</code></pre><table><tbody><tr><th>Topic</th><td>${text}</td></tr></tbody></table>`,
                  },
                  {
                      id: 'services',
                      type: 'services',
                      visible: true,
                      title: 'Services',
                      items: [
                          { title: 'Service information', description: text },
                          { title: 'Further services', description: text },
                      ],
                  },
                  {
                      id: 'strengths',
                      type: 'strengths',
                      visible: true,
                      title: 'Strengths',
                      items: [
                          { title: 'Published strengths', description: text },
                      ],
                  },
                  {
                      id: 'news',
                      type: 'articles',
                      visible: true,
                      title: 'Recent articles',
                      featured: false,
                      limit: 3,
                  },
                  {
                      id: 'contact',
                      type: 'contact',
                      visible: true,
                      title: 'Contact',
                      description: text,
                      cta: {
                          label: 'Send email',
                          href: 'mailto:contact@example.test',
                          active: false,
                      },
                  },
                  {
                      id: 'hidden',
                      type: 'introduction',
                      visible: false,
                      title: 'PRIVATE HIDDEN SECTION',
                      body: '<p>Hidden</p>',
                  },
                  {
                      id: 'empty-services',
                      type: 'services',
                      visible: true,
                      title: 'EMPTY SERVICES',
                      items: [],
                  },
              ];
    return {
        preset,
        preferences: Object.fromEntries(
            ['corporate', 'editorial', 'minimal'].map((name) => [
                name,
                { sectionOrder: [], hiddenSections: [] },
            ]),
        ),
        navigation: [],
        breadcrumbs: [
            { label: 'Home', href: '/', active: false },
            { label: kind, href: '#', active: true },
        ],
        page: {
            kind,
            title: `Public ${kind} fixture`,
            sections,
            body: kind === 'article' ? `<p>${text}</p>` : '',
            seo: {},
            article:
                kind === 'article'
                    ? {
                          author: 'Fixture author',
                          publishedAt: '2026-10-17',
                          publishedLabel: '17 October 2026',
                      }
                    : undefined,
        },
        articles:
            state === 'empty'
                ? []
                : Array.from({ length: 3 }, (_, index) => ({
                      id: String(index),
                      title: `Publication ${index} ${long ? text : ''}`,
                      excerpt: text,
                      href: `#publication-${index}`,
                      cover: state === 'typical' ? image : null,
                      featured: index === 0,
                      publishedAt: '2026-10-17',
                      publishedLabel: '17 October 2026',
                  })),
        pagination: [
            { label: '1', href: '#page-1', active: true },
            { label: '2', href: '#page-2', active: false },
        ],
        contact: {
            email: 'contact@example.test',
            phone: '+620000',
            address: text,
            hours: 'Monday–Friday',
            location_url: 'https://example.test/location',
            social_links: [],
        },
    };
}
const ordered = website('corporate', 'home', 'typical');
ordered.preferences.corporate = {
    sectionOrder: ['contact', 'hero'],
    hiddenSections: ['services'],
};
const composed = composePublicSections(
    ordered.page.sections,
    publicPresets.corporate,
    ordered.preferences.corporate,
);
assert.deepEqual(
    composed.slice(0, 2).map((section) => section.id),
    ['contact', 'hero'],
);
assert(
    !composed.some((section) => ['hidden', 'services'].includes(section.id)),
);
assert.equal(
    ordered.page.sections[0].id,
    'hero',
    'Composition must preserve canonical content',
);

const server = createHttpServer(async (request, response) => {
    if (!request.url.startsWith('/fixture?'))
        return vite.middlewares(request, response, () => {
            response.statusCode = 404;
            response.end();
        });
    try {
        const params = new URL(request.url, 'http://localhost').searchParams;
        const data = website(
            params.get('preset'),
            params.get('kind'),
            params.get('state'),
        );
        const locale = params.get('locale') ?? 'en';
        const html = await renderToString(fixture.createFixture(data, locale));
        assert(!html.includes('PRIVATE HIDDEN SECTION'));
        assert(!html.includes('EMPTY SERVICES'));
        assert.equal((html.match(/<h1[ >]/g) ?? []).length, 1);
        const source = `<!doctype html><html lang="${locale}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/resources/css/app.css"></head><body><div id="app">${html}</div><script type="module">import { createFixture } from '/tests/Browser/Fixtures/public-preset.ts'; createFixture(${JSON.stringify(data).replaceAll('<', '\\u003c')}, ${JSON.stringify(locale)}).mount('#app'); window.fixtureHydrated = true;</script></body></html>`;
        response.setHeader('Content-Type', 'text/html; charset=utf-8');
        response.end(await vite.transformIndexHtml(request.url, source));
    } catch (error) {
        response.statusCode = 500;
        response.setHeader('Content-Type', 'text/plain; charset=utf-8');
        response.end(String(error.stack));
    }
});
await new Promise((done) => server.listen(0, '127.0.0.1', done));
let browser;
try {
    browser = await chromium.launch({ headless: true });
    await mkdir('/tmp/jcf-scope03', { recursive: true });
    const page = await browser.newPage();
    const errors = [];
    page.on('pageerror', (error) => errors.push(String(error)));
    page.on('console', (message) => {
        if (['error', 'warning'].includes(message.type()))
            errors.push(message.text());
    });
    let count = 0;
    for (const preset of ['corporate', 'editorial', 'minimal']) {
        for (const kind of [
            'home',
            'about',
            'contact',
            'article-index',
            'article',
        ]) {
            for (const state of ['empty', 'typical', 'long', 'missing-image']) {
                const locale = count % 2 ? 'id' : 'en';
                await page.setViewportSize({ width: 390, height: 844 });
                const url = `http://127.0.0.1:${server.address().port}/fixture?preset=${preset}&kind=${kind}&state=${state}&locale=${locale}`;
                const response = await page.goto(url);
                assert.equal(response.status(), 200, await response.text());
                await page.waitForFunction(
                    () => window.fixtureHydrated === true,
                );
                assert(
                    await page.evaluate(() =>
                        [
                            ...document.querySelectorAll('[aria-labelledby]'),
                        ].every((element) =>
                            element
                                .getAttribute('aria-labelledby')
                                .split(/\s+/)
                                .every((id) => document.getElementById(id)),
                        ),
                    ),
                    `Dangling aria-labelledby: ${preset}/${kind}/${state}`,
                );
                await page.emulateMedia({ reducedMotion: 'reduce' });
                for (const width of [390, 1440]) {
                    await page.setViewportSize({ width, height: 900 });
                    assert(
                        await page.evaluate(
                            () =>
                                document.documentElement.scrollWidth <=
                                innerWidth,
                        ),
                        `Overflow: ${preset}/${kind}/${state}/${width}`,
                    );
                    assert.equal(await page.locator('h1').count(), 1);
                    if (kind === 'home' && state === 'typical')
                        await page.screenshot({
                            path: `/tmp/jcf-scope03/${preset}-${width}.png`,
                            fullPage: true,
                        });
                }
                if (state === 'typical') {
                    await page
                        .getByRole('link', { name: 'Contact information' })
                        .click();
                    assert.equal(new URL(page.url()).hash, '#contact');
                }
                if (kind === 'article-index' && state === 'empty')
                    assert(await page.getByRole('status').isVisible());
                count++;
            }
        }
    }
    assert.deepEqual(
        errors,
        [],
        'Browser exceptions, hydration warnings and console errors',
    );
    console.log(
        `PASS ${count} SSR + hydration cases, 120 viewport checks, both locales, reduced motion, section preferences, anchor interaction.`,
    );
} finally {
    await browser?.close();
    await new Promise((done) => server.close(done));
    await vite.close();
}
