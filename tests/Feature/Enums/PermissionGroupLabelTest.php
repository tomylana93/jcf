<?php

declare(strict_types=1);

use App\Enums\Permission;

describe('Permission group labels', function (): void {
    it('translates every permission group instead of exposing the raw prefix', function (string $locale): void {
        app()->setLocale($locale);

        $labels = collect(Permission::cases())->mapWithKeys(fn (Permission $permission): array => [$permission->group() => $permission->groupLabel()]);

        expect($labels->filter(fn (string $label): bool => str_starts_with($label, 'permission.group.'))->all())->toBeEmpty();
    })->with(['en', 'id']);

    it('maps hyphenated groups to their translated label', function (): void {
        app()->setLocale('id');

        $label = Permission::ManageApiClients->groupLabel();

        expect($label)->toBe('Klien API');
    });
});
