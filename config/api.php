<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | API Domain
    |--------------------------------------------------------------------------
    |
    | Host that serves the external API, for example "api.example.com". API
    | routes are only registered when the "api" feature is enabled and this
    | value is filled, and web routes are rejected on this host.
    |
    */

    'domain' => env('API_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limit
    |--------------------------------------------------------------------------
    |
    | Requests per minute, counted per access token for authenticated
    | requests and per IP address otherwise.
    |
    */

    'rate_limit' => (int) env('API_RATE_LIMIT', 60),

    /*
    |--------------------------------------------------------------------------
    | Device Token Lifetime
    |--------------------------------------------------------------------------
    |
    | Days a device token issued by the login endpoint stays valid. The
    | device must log in again afterwards; expired tokens are pruned daily.
    |
    */

    'device_token_days' => (int) env('API_DEVICE_TOKEN_DAYS', 30),

];
