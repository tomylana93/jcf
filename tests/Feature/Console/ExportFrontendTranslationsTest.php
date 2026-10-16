<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

describe('Frontend translations', function (): void {
    it('rejects a non-string output path', function (): void {
        artisan('lang:export', ['--output' => ['messages.json']])
            ->expectsOutput('The output path must be a string.')
            ->assertFailed();
    });

    it('exports the application language groups to the default JSON destination', function (): void {
        artisan('lang:export')->assertSuccessful();

        $messages = json_decode(File::get(resource_path('js/lang/messages.json')), true, flags: JSON_THROW_ON_ERROR);

        expect($messages['en']['auth']['failed'])->toBe('The provided credentials do not match the account records.')
            ->and($messages['en']['auth']['throttle'])->toContain('{seconds}')
            ->and($messages['id']['auth']['throttle'])->toContain('{seconds}');
    });

    it('converts nested placeholders and literal syntax and refreshes removed messages', function (): void {
        $directory = sys_get_temp_dir().'/frontend-translations-'.uniqid();
        $originalLangPath = app()->langPath();
        File::ensureDirectoryExists($directory.'/lang/en');
        File::put($directory.'/lang/en/example.php', '<?php return ["nested" => ["message" => "Wait :seconds seconds. email@example.com {literal} | text"], "removed" => "Old"];');
        File::put($directory.'/lang/en/ignored.txt', 'Not a PHP language file');
        app()->useLangPath($directory.'/lang');

        try {
            artisan('lang:export', ['--output' => $directory.'/output/messages.json'])->assertSuccessful();

            $messages = json_decode(File::get($directory.'/output/messages.json'), true, flags: JSON_THROW_ON_ERROR);
            expect($messages['en']['example']['nested']['message'])->toBe("Wait {seconds} seconds. email{'@'}example.com {'{'}literal{'}'} {'|'} text");

            File::put($directory.'/lang/en/example.php', '<?php return ["updated" => "New"];');
            artisan('lang:export', ['--output' => $directory.'/output/messages.json'])->assertSuccessful();

            expect(json_decode(File::get($directory.'/output/messages.json'), true, flags: JSON_THROW_ON_ERROR))
                ->toBe(['en' => ['example' => ['updated' => 'New']]]);
        } finally {
            app()->useLangPath($originalLangPath);
            File::deleteDirectory($directory);
        }
    });
});
