<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

describe('Security headers', function (): void {
    it('protects pages against framing, sniffing and referrer leaks', function (): void {
        get(route('login'))->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy')
            ->assertHeaderMissing('Content-Security-Policy')
            ->assertHeaderMissing('Strict-Transport-Security');
    });

    it('reports a nonce based content security policy that the inline script satisfies', function (): void {
        $response = get(route('login'))->assertOk();
        $nonce = Vite::cspNonce();
        $policy = (string) $response->headers->get('Content-Security-Policy-Report-Only');

        expect($nonce)->toBeString()->not->toBeEmpty()
            ->and($policy)->toContain("script-src 'self' 'nonce-{$nonce}'")
            ->toContain("frame-ancestors 'self'")
            ->toContain("object-src 'none'")
            ->not->toContain("'unsafe-eval'");
        $response->assertSeeHtml('<script nonce="'.$nonce.'">');
    });

    it('allows the configured realtime server in the policy', function (): void {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.options' => ['host' => 'ws.example.test', 'port' => 443, 'scheme' => 'https'],
        ]);

        expect((string) get(route('login'))->headers->get('Content-Security-Policy-Report-Only'))
            ->toContain("connect-src 'self' wss://ws.example.test:443");
    });

    it('leaves the realtime server out when no host is configured', function (): void {
        Vite::useHotFile(storage_path('framework/testing/missing-vite.hot'));
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.options' => ['host' => '', 'port' => 443, 'scheme' => 'https'],
        ]);

        expect((string) get(route('login'))->headers->get('Content-Security-Policy-Report-Only'))
            ->toEndWith("connect-src 'self'");
    });

    it('allows the Vite dev server origin and its websocket while it is running', function (): void {
        $hotFile = storage_path('framework/testing/csp-vite.hot');
        File::put($hotFile, 'https://vite.example.test:5173');
        Vite::useHotFile($hotFile);

        try {
            $policy = (string) get(route('login'))->headers->get('Content-Security-Policy-Report-Only');
        } finally {
            File::delete($hotFile);
        }

        expect($policy)->toContain("script-src 'self' 'nonce-".Vite::cspNonce()."' https://vite.example.test:5173")
            ->toContain('wss://vite.example.test:5173');
    });

    it('sends HSTS only for secure production requests', function (bool $production, bool $secure, bool $expected): void {
        app()->instance('env', $production ? 'production' : 'local');
        $path = route('login', absolute: false);

        $response = get(($secure ? 'https://' : 'http://').parse_url((string) config('app.url'), PHP_URL_HOST).$path);

        $expected
            ? $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains')
            : $response->assertHeaderMissing('Strict-Transport-Security');
    })->with([
        'secure production' => [true, true, true],
        'insecure production' => [true, false, false],
        'secure local' => [false, true, false],
    ]);

    it('adds the headers to JSON error responses', function (): void {
        getJson('/missing-page')->assertNotFound()->assertHeader('X-Content-Type-Options', 'nosniff');
    });
});
