<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Comma-separated addresses or CIDR ranges of reverse proxies whose
    | X-Forwarded-* headers describe the real client, scheme and host. Leave
    | empty when clients connect directly; "*" trusts any calling proxy and
    | is only safe when the application port is unreachable except through it.
    |
    */

    'proxies' => env('TRUSTED_PROXIES') ?: null,

];
