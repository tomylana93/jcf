<?php

declare(strict_types=1);

use App\Models\User;
use App\Settings\GeneralSettings;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withSession;

describe('Public home', function (): void {
    it('renders saved content and canonical metadata without consuming intended URLs', function (bool $authenticated): void {
        $settings = resolve(GeneralSettings::class);
        $settings->public_pages_enabled = true;
        $settings->site_name = 'JCF <Logistics>';
        $settings->site_description = 'Shipping & tracking';
        $settings->save();
        config(['app.url' => 'https://canonical.example/']);
        withSession(['url.intended' => route('profile.edit')]);
        if ($authenticated) {
            actingAs(User::factory()->create());
        }

        get('/?campaign=example')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('public/Home')
                ->where('canonicalUrl', 'https://canonical.example/')
                ->where('general.site_name', 'JCF <Logistics>')
                ->where('general.site_description', 'Shipping & tracking'))
            ->assertSee('<meta property="og:title" content="JCF &lt;Logistics&gt;"', false)
            ->assertSee('<meta name="twitter:description" content="Shipping &amp; tracking"', false)
            ->assertSee('<link rel="canonical" href="https://canonical.example/"', false)
            ->assertSessionHas('url.intended', route('profile.edit'));
    })->with([false, true]);

    it('redirects guests to login when disabled and retains the intended URL', function (): void {
        $settings = resolve(GeneralSettings::class);
        $settings->public_pages_enabled = false;
        $settings->save();

        withSession(['url.intended' => route('profile.edit')]);

        get('/')->assertRedirect(route('login'))->assertSessionHas('url.intended', route('profile.edit'));
    });

    it('redirects signed-in users to their intended destination when disabled', function (): void {
        $settings = resolve(GeneralSettings::class);
        $settings->public_pages_enabled = false;
        $settings->save();

        actingAs(User::factory()->create());
        withSession(['url.intended' => route('profile.edit')]);

        get('/')->assertRedirect(route('profile.edit'))->assertSessionMissing('url.intended');
    });

    it('redirects signed-in users to dashboard without an intended destination', function (): void {
        $settings = resolve(GeneralSettings::class);
        $settings->public_pages_enabled = false;
        $settings->save();

        actingAs(User::factory()->create());

        get('/')->assertRedirect(route('dashboard'));
    });

    it('redirects the home page until public pages are enabled', function (): void {
        $settings = resolve(GeneralSettings::class);
        $settings->public_pages_enabled = false;
        $settings->save();

        get(route('home'))->assertRedirect(route('login'));

        $settings = resolve(GeneralSettings::class);
        $settings->public_pages_enabled = true;
        $settings->save();

        get(route('home'))->assertInertia(fn (Assert $page) => $page->component('public/Home'));
    });
});
