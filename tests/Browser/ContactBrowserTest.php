<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\ContactMessage;
use App\Settings\GeneralSettings;
use App\Settings\WebsiteSettings;
use Illuminate\Support\Facades\Process;

use function Pest\Laravel\get;

describe('Contact form and inbox interactions', function (): void {
    it('renders through production SSR and submits a message in each preset', function (string $preset): void {
        $general = resolve(GeneralSettings::class);
        $general->public_pages_enabled = true;
        $general->save();
        $settings = resolve(WebsiteSettings::class);
        $settings->preset = $preset;
        $settings->save();
        $payload = get('/contact')->viewData('page');
        Process::preventStrayProcesses(false);
        $script = <<<'JS_WRAP'
        let input='';for await(const part of process.stdin){input+=part;}const {default:render}=await import('./bootstrap/ssr/app.js');const result=await render(JSON.parse(input));await new Promise(resolve=>process.stdout.write('CONTACT_SSR_RESULT:'+JSON.stringify(result)+'CONTACT_SSR_END',resolve));process.exit(0);
        JS_WRAP;
        $result = Process::path(base_path())->input(json_encode($payload, JSON_THROW_ON_ERROR))->run(['node', '--input-type=module', '-e', $script]);
        expect($result->exitCode())->toBe(0, $result->errorOutput());
        $rendered = json_decode(explode('CONTACT_SSR_END', explode('CONTACT_SSR_RESULT:', $result->output(), 2)[1], 2)[0], true, flags: JSON_THROW_ON_ERROR);
        expect($rendered['body'])->toContain('contact-name', 'contact-email', 'contact-message', 'type="submit"')->and(implode('', $rendered['head']))->toContain('rel="canonical"');
        visit('/contact')->assertSee(__('contact.title.form'))
            ->fill('name', 'Browser visitor')->fill('email', 'browser@example.com')->fill('subject', 'Browser enquiry')->fill('message', 'Please send service details.')
            ->screenshot(filename: 'scope-six-'.$preset.'-contact-desktop')
            ->click('@contact_send')->assertSee(__('contact.status.sent'))->assertValue('message', '')->assertNoJavaScriptErrors();
        expect(ContactMessage::query()->sole()->message)->toBe('Please send service details.');
        visit('/contact')->on()->mobile()->assertSee(__('contact.title.form'))->assertNoJavaScriptErrors()->screenshot(filename: 'scope-six-'.$preset.'-contact-mobile');
    })->with(['corporate', 'editorial', 'minimal']);

    it('keeps input after validation and supports correction', function (): void {
        $general = resolve(GeneralSettings::class);
        $general->public_pages_enabled = true;
        $general->save();
        $page = visit('/contact')->fill('name', 'Browser visitor')->fill('email', 'browser@example.com')->fill('subject', 'Browser enquiry')->fill('message', 'Retained input');
        $page->script("document.querySelector('form').noValidate = true; document.querySelector('#contact-email').value = 'invalid'; document.querySelector('#contact-email').dispatchEvent(new Event('input', {bubbles:true}));");
        $page->click('@contact_send')->assertSee(__('contact.error.invalid'))->assertValue('message', 'Retained input')->assertAttribute('#contact-email', 'aria-invalid', 'true')->assertNoJavaScriptErrors();
        expect(ContactMessage::query()->count())->toBe(0);
        $page->fill('email', 'valid@example.com')->click('@contact_send')->assertSee(__('contact.status.sent'))->assertNoJavaScriptErrors();
    });

    it('retains input and explains failed submissions', function (string $failure, string $key): void {
        $general = resolve(GeneralSettings::class);
        $general->public_pages_enabled = true;
        $general->save();
        $page = visit('/contact')->fill('name', 'Browser visitor')->fill('email', 'browser@example.com')->fill('subject', 'Browser enquiry')->fill('message', 'Keep this message');
        $page->script("window.contactOriginalSend = XMLHttpRequest.prototype.send;
            XMLHttpRequest.prototype.send = function () {
                const xhr = this;
                if ('{$failure}' === 'network') {
                    throw new Error('Simulated connection failure');
                } else {
                    Object.defineProperty(xhr, 'status', {value: Number('{$failure}')});
                    Object.defineProperty(xhr, 'responseText', {value: '{}'});
                    Object.defineProperty(xhr, 'response', {value: '{}'});
                    Object.defineProperty(xhr, 'readyState', {value: 4});
                    setTimeout(() => {xhr.dispatchEvent(new Event('load')); xhr.dispatchEvent(new Event('loadend'));}, 0);
                }
            }; true;");
        $page->click('@contact_send')->assertSee(__($key))->assertValue('message', 'Keep this message')->assertEnabled('@contact_send')->assertNoJavaScriptErrors();
        expect(ContactMessage::query()->count())->toBe(0);
    })->with([['network', 'contact.error.network'], ['500', 'contact.error.server'], ['429', 'contact.error.throttled'], ['419', 'contact.error.session']]);

    it('escapes message contents and toggles inbox status', function (): void {
        actingAsUserWithPermissions(Permission::ViewContactInbox);
        $message = ContactMessage::factory()->create(['subject' => 'Browser inbox message', 'message' => '<script>window.contactInjected = true</script>']);
        $page = visit('/website/inbox')->assertSee('Browser inbox message')->click('Browser inbox message')
            ->assertSee('<script>window.contactInjected = true</script>')->assertScript('window.contactInjected === undefined')
            ->click('@inbox_toggle')->assertSee(__('contact.status.handled'))->assertNoJavaScriptErrors()->screenshot(filename: 'scope-six-inbox-desktop');
        expect($message->fresh()->handled_at)->not->toBeNull();
        $page->click('@inbox_toggle')->assertSee(__('contact.status.unhandled'))->assertNoJavaScriptErrors();
        expect($message->fresh()->handled_at)->toBeNull();
        visit('/website/inbox/'.$message->id)->on()->mobile()->assertSee('Browser inbox message')->assertNoJavaScriptErrors()->screenshot(filename: 'scope-six-inbox-mobile');
    });
});
