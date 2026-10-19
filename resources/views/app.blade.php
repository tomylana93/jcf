<!DOCTYPE html>
<html data-brand-color="{{ app(\App\Settings\BrandSettings::class)->color->value }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        @if (app(\App\Settings\BrandSettings::class)->favicon_path)
            <link rel="icon" href="{{ Storage::disk('public')->url(app(\App\Settings\BrandSettings::class)->favicon_path) }}">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        @endif
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ $page['props']['website']['page']['seo']['title'] ?? $page['props']['general']['site_name'] }}</title>
            @if (isset($page['props']['website']['page']['seo']))
                @php($seo = $page['props']['website']['page']['seo'])
                <meta name="description" content="{{ $seo['description'] }}" data-inertia="description">
                <meta name="robots" content="{{ $seo['robots'] }}" data-inertia="robots">
                <link rel="canonical" href="{{ $seo['canonicalUrl'] }}" data-inertia="canonical">
                @foreach (['type' => $seo['type'], 'title' => $seo['title'], 'description' => $seo['description'], 'url' => $seo['canonicalUrl'], 'site_name' => $seo['siteName'], 'locale' => str_replace('-', '_', $seo['locale'])] as $key => $value)
                    <meta property="og:{{ $key }}" content="{{ $value }}" data-inertia="og:{{ $key }}">
                @endforeach
                @foreach (['card' => $seo['image'] ? 'summary_large_image' : 'summary', 'title' => $seo['title'], 'description' => $seo['description']] as $key => $value)
                    <meta name="twitter:{{ $key }}" content="{{ $value }}" data-inertia="twitter:{{ $key }}">
                @endforeach
                @if ($seo['image'])
                    <meta property="og:image" content="{{ $seo['image']['url'] }}" data-inertia="og:image">
                    <meta property="og:image:alt" content="{{ $seo['image']['alt'] }}" data-inertia="og:image:alt">
                    <meta name="twitter:image" content="{{ $seo['image']['url'] }}" data-inertia="twitter:image">
                    <meta name="twitter:image:alt" content="{{ $seo['image']['alt'] }}" data-inertia="twitter:image:alt">
                @endif
                @if ($seo['structuredData'])
                    <script type="application/ld+json" data-inertia="public-schema">{!! $seo['structuredData'] !!}</script>
                @endif
            @endif
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
