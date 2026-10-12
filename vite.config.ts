import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ]),
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    lint: {
        jsPlugins: ['@stylistic/eslint-plugin'],
        rules: {
            '@stylistic/padding-line-between-statements': [
                'error',
                {
                    blankLine: 'always',
                    prev: ['const', 'let', 'var'],
                    next: ['const', 'let', 'var'],
                },
            ],
        },
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        sortImports: {
            groups: [
                'builtin',
                'external',
                ['internal', 'subpath'],
                ['parent', 'sibling', 'index'],
                'style',
                'type',
                'unknown',
            ],
            internalPattern: ['@/'],
            newlinesBetween: true,
            order: 'asc',
            sortSideEffects: false,
        },
        ignorePatterns: [
            '.github/**',
            '.lerd.yaml',
            'composer.json',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
            '.serena/**',
            '.vscode/**',
            '.ai/**',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
