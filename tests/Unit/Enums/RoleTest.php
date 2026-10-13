<?php

declare(strict_types=1);

use App\Enums\Role;

describe('Role attributes', function (): void {
    test('grants no direct permissions to the superadmin role', function (): void {
        expect(Role::SuperAdmin->permissions())->toBeEmpty();
    });

    test('marks the superadmin badge red', function (): void {
        expect(Role::SuperAdmin->badgeColor())->toBe('red');
    });
});
