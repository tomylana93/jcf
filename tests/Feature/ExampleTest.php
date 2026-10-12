<?php

declare(strict_types=1);

use function Pest\Laravel\get;

describe('home page', function () {
    it('renders the home page', function () {
        $response = get(route('home'));

        $response->assertOk();
    });
});
