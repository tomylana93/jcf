<?php

declare(strict_types=1);

use App\Enums\Role;

describe('User enum options', function (): void {
    it('provides the superadmin option with its label and color', function (string $locale): void {
        app()->setLocale($locale);

        expect(Role::options())->toBe([
            ['value' => 'superadmin', 'label' => 'Super Admin', 'name' => 'SuperAdmin', 'color' => 'red'],
        ]);
    })->with(['en', 'id']);
});
