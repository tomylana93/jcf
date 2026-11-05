<?php

declare(strict_types=1);

use App\Enums\Role;

describe('Role attributes', function (): void {
    it('grants no direct permissions to the superadmin role', function (): void {
        expect(Role::SuperAdmin->permissions())->toBeEmpty();
    });

    it('marks the superadmin badge red', function (): void {
        expect(Role::SuperAdmin->badgeColor())->toBe('red');
    });
});
