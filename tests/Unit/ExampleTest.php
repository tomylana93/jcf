<?php

declare(strict_types=1);

describe('Boolean expectations', function () {
    it('recognizes a true value', function () {
        expect(true)->toBeTrue();
    });
});
