<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withCookie;
use function Pest\Laravel\withUnencryptedCookie;

describe('Browser locale preferences', function (): void {
    it('uses a browser cookie on public pages', function (string $locale): void {
        withUnencryptedCookie('locale', $locale)->get(route('login'))
            ->assertInertia(fn (Assert $page): Assert => $page->where('locale.current', $locale));
    })->with(['English' => 'en', 'Indonesian' => 'id']);

    it('uses the browser cookie for authenticated users without changing their account', function (): void {
        $user = User::factory()->create(['updated_at' => now()->subDay()]);
        $originalAttributes = $user->only(['name', 'email', 'updated_at']);
        actingAs($user);

        withUnencryptedCookie('locale', 'id')->get(route('dashboard'))
            ->assertInertia(fn (Assert $page): Assert => $page->where('locale.current', 'id'));

        expect($user->refresh()->only(['name', 'email', 'updated_at']))->toEqual($originalAttributes);
    });

    it('ignores unsupported cookie values', function (string $locale): void {
        app()->setLocale('id');

        withUnencryptedCookie('locale', $locale)->get(route('login'))
            ->assertCookieExpired('locale')
            ->assertInertia(fn (Assert $page): Assert => $page->where('locale.current', 'id'));
    })->with(['unsupported language' => 'fr', 'empty value' => '', 'path' => '../en']);

    it('clears legacy encrypted cookies so the browser can write the preference directly', function (): void {
        app()->setLocale('en');

        withCookie('locale', 'id')->get(route('login'))
            ->assertCookieExpired('locale')
            ->assertInertia(fn (Assert $page): Assert => $page->where('locale.current', 'en'));
    });

    it('shares translated enum options using the browser locale', function (string $locale, array $labels): void {
        withUnencryptedCookie('locale', $locale)->get(route('login'))
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('locale.current', $locale)
                ->where('locale.options', [
                    ['value' => 'en', 'label' => $labels[0], 'name' => 'English'],
                    ['value' => 'id', 'label' => $labels[1], 'name' => 'Indonesian'],
                ])
                ->missing('locale.flags'));
    })->with([
        'English' => ['en', ['English', 'Indonesian']],
        'Indonesian' => ['id', ['Bahasa Inggris', 'Bahasa Indonesia']],
    ]);

    it('uses the application locale when no cookie exists', function (): void {
        app()->setLocale('id');

        get(route('login'))
            ->assertInertia(fn (Assert $page): Assert => $page->where('locale.current', 'id'));
    });

    it('allows the cookie to override the application locale', function (): void {
        app()->setLocale('id');

        withUnencryptedCookie('locale', 'en')->get(route('login'))
            ->assertInertia(fn (Assert $page): Assert => $page->where('locale.current', 'en'));
    });

    it('falls back to English when the application locale is unsupported', function (): void {
        app()->setLocale('fr');

        get(route('login'))
            ->assertInertia(fn (Assert $page): Assert => $page->where('locale.current', 'en'));
    });

    it('keeps the browser language when logging out', function (): void {
        $user = User::factory()->create();
        actingAs($user);
        withUnencryptedCookie('locale', 'id');

        post(route('logout'))->assertRedirect(route('home'));
        Auth::forgetGuards();

        get(route('login'))
            ->assertInertia(fn (Assert $page): Assert => $page->where('locale.current', 'id'));
    });

    it('does not carry a previous request locale after the cookie is cleared', function (): void {
        app()->setLocale('en');
        withUnencryptedCookie('locale', 'id')->get(route('login'))
            ->assertInertia(fn (Assert $page): Assert => $page->where('locale.current', 'id'));

        withUnencryptedCookie('locale', '')->get(route('login'))
            ->assertInertia(fn (Assert $page): Assert => $page->where('locale.current', 'en'));
    });
});
