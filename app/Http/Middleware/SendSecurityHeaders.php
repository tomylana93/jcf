<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hardens every response against framing, MIME sniffing and referrer leaks, and reports Content Security Policy violations.
 *
 * The policy is sent as Report-Only until violations have been reviewed; it allows same-origin framing for the mail preview.
 */
final class SendSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $response->headers->set('Content-Security-Policy-Report-Only', $this->contentSecurityPolicy());

        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $devServer = $this->devServerOrigin();
        $devSources = $devServer === null ? [] : [$devServer];
        $devSockets = $devServer === null ? [] : [preg_replace('#^http#', 'ws', $devServer)];

        $directives = [
            'default-src' => ["'self'"],
            'base-uri' => ["'self'"],
            'object-src' => ["'none'"],
            'frame-ancestors' => ["'self'"],
            'form-action' => ["'self'"],
            'img-src' => ["'self'", 'data:', 'blob:', ...$devSources],
            'font-src' => ["'self'", 'data:', ...$devSources],
            'style-src' => ["'self'", "'unsafe-inline'", ...$devSources],
            'script-src' => ["'self'", "'nonce-".Vite::cspNonce()."'", ...$devSources],
            'connect-src' => ["'self'", ...$this->realtimeSources(), ...$devSources, ...$devSockets],
        ];

        return implode('; ', array_map(
            fn (string $directive, array $sources): string => $directive.' '.implode(' ', array_unique($sources)),
            array_keys($directives),
            $directives,
        ));
    }

    /** @return list<string> */
    private function realtimeSources(): array
    {
        if (config('broadcasting.default') !== 'reverb') {
            return [];
        }

        $options = (array) config('broadcasting.connections.reverb.options');
        $host = $options['host'] ?? null;

        if (! is_string($host) || $host === '') {
            return [];
        }

        $scheme = ($options['scheme'] ?? 'https') === 'https' ? 'wss' : 'ws';
        $port = $options['port'] ?? null;

        return [$scheme.'://'.$host.(is_numeric($port) ? ':'.$port : '')];
    }

    private function devServerOrigin(): ?string
    {
        if (! Vite::isRunningHot()) {
            return null;
        }

        $url = mb_trim(File::get(Vite::hotFile()));
        $parts = parse_url($url);

        return isset($parts['scheme'], $parts['host'])
            ? $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '')
            : null;
    }
}
