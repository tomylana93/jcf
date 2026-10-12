import { execFileSync, execSync } from 'node:child_process';
import { resolve, sep } from 'node:path';

import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import typescript6 from '@typescript/typescript6';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import type { Plugin } from 'vite-plus';
import { defineConfig, lazyPlugins } from 'vite-plus';
import { registerTS } from 'vue/compiler-sfc';

registerTS(
    () =>
        typescript6 as unknown as ReturnType<Parameters<typeof registerTS>[0]>,
);

function laravelTranslations(): Plugin {
    const generate = () => {
        if (process.platform === 'win32') {
            return execSync('php artisan lang:export --no-interaction', {
                stdio: 'inherit',
            });
        }

        return execFileSync(
            'php',
            ['artisan', 'lang:export', '--no-interaction'],
            {
                stdio: 'inherit',
            },
        );
    };
    return {
        name: 'laravel-translations',
        enforce: 'pre',
        buildStart() {
            generate();
        },
        configureServer(server) {
            const directory = resolve('lang') + sep;
            server.watcher.add(directory);
            server.watcher.on('all', (event, file) => {
                if (
                    ['add', 'change', 'unlink'].includes(event) &&
                    resolve(file).startsWith(directory) &&
                    file.endsWith('.php')
                ) {
                    try {
                        generate();
                    } catch (error) {
                        server.config.logger.error(String(error));
                    }
                }
            });
        },
    };
}

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravelTranslations(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                    optimizedFallbacks: false,
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
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/lang/**',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
            'vite.config.ts',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
            typeCheck: true,
        },
        plugins: ['eslint', 'typescript', 'unicorn', 'oxc', 'vue'],
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            '.lerd.yaml',
            'composer.json',
            'resources/js/components/ui/*',
            'resources/js/lang/**',
            'resources/views/mail/*',
            '.serena/**',
            '.vscode/**',
            '.ai/**',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
        sortImports: {
            groups: [
                'builtin',
                'external',
                'internal',
                'parent',
                'sibling',
                'index',
            ],
            newlinesBetween: true,
        },
    },
});
