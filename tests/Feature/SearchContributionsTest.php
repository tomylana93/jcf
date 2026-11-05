<?php

declare(strict_types=1);

use App\Actions\System\GetSystemHealth;
use App\Enums\HealthDetail;
use App\Models\User;
use App\Support\Contracts\ChatSummary;
use App\Support\Contracts\HealthCheck;
use App\Support\Contracts\SearchProvider;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

describe('Module contributions', function (): void {
    it('includes registered search providers without changing the coordinator', function (): void {
        app()->instance('test.search', new class implements SearchProvider
        {
            public function key(): string
            {
                return 'extra';
            }

            public function handle(User $user, string $query): array
            {
                return [['id' => $user->id, 'title' => $query]];
            }
        });
        app()->tag('test.search', SearchProvider::class);
        $actor = User::factory()->create();
        actingAs($actor);

        getJson(route('search', ['query' => 'needle']))->assertOk()
            ->assertJsonPath('extra', [['id' => $actor->id, 'title' => 'needle']]);
    });

    it('uses the chat summary contract in shared props', function (): void {
        app()->instance(ChatSummary::class, new class implements ChatSummary
        {
            public function handle(?User $actor): array
            {
                return ['unread_count' => $actor === null ? 0 : 42];
            }
        });
        actingAs(User::factory()->create());

        get(route('dashboard'))->assertInertia(fn ($page) => $page->where('chatSummary.unread_count', 42));
    });

    it('includes registered health checks and isolates their failures', function (): void {
        app()->instance('test.health.ok', new class implements HealthCheck
        {
            public function name(): string
            {
                return 'extra';
            }

            public function handle(): HealthDetail
            {
                return HealthDetail::Reachable;
            }
        });
        app()->instance('test.health.failing', new class implements HealthCheck
        {
            public function name(): string
            {
                return 'broken';
            }

            public function handle(): HealthDetail
            {
                throw new RuntimeException('Sensitive detail.');
            }
        });
        app()->tag(['test.health.ok', 'test.health.failing'], HealthCheck::class);

        $checks = collect(resolve(GetSystemHealth::class)->handle())->keyBy('name');

        expect($checks['extra'])->toBe(['name' => 'extra', 'status' => 'ok', 'detail' => 'reachable'])
            ->and($checks['broken'])->toBe(['name' => 'broken', 'status' => 'failed', 'detail' => 'unavailable'])
            ->and($checks)->toHaveKey('backup');
    });
});
