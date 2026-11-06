<?php

declare(strict_types=1);

use function Pest\Laravel\withUnencryptedCookie;

describe('Appearance preference', function (): void {
    it('passes only known theme values to the inline script', function (string $cookie, string $expected): void {
        withUnencryptedCookie('appearance', $cookie)->get(route('login'))->assertOk()->assertSeeHtml("const appearance = '{$expected}';");
    })->with([
        'dark' => ['dark', 'dark'],
        'light' => ['light', 'light'],
        'backslash' => ['\\', 'system'],
        'markup' => ["'</script><script>alert(1)</script>", 'system'],
    ]);
});
