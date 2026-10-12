<?php

declare(strict_types=1);

describe('boolean expectations', function () {
    it('recognizes a true value', function () {
        expect(true)->toBeTrue();
    });
});
