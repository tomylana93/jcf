<?php

declare(strict_types=1);

use function Pest\Laravel\get;

describe('home page', function () {
    it('redirects the default home page to login', function () {
        $response = get(route('home'));

        $response->assertRedirect(route('login'));
    });
});
