<?php

declare(strict_types=1);

use Spatie\TemporaryDirectory\TemporaryDirectory;
use Symfony\Component\Process\Process;

function finalizationFixture(string $version = "1.2.3\n"): TemporaryDirectory
{
    $directory = (new TemporaryDirectory)->create();
    copy(dirname(__DIR__, 2).'/bootstrap/finalize-project.php', $directory->path('bootstrap/finalize-project.php'));
    file_put_contents($directory->path('version.txt'), $version);

    return $directory;
}

function finalizeFixture(TemporaryDirectory $directory): Process
{
    $process = new Process([PHP_BINARY, 'bootstrap/finalize-project.php'], $directory->path(), ['http_proxy' => 'http://127.0.0.1:1', 'https_proxy' => 'http://127.0.0.1:1']);
    $process->run();

    return $process;
}

describe('Application release identity', function (): void {
    it('attaches finalization only to project creation rather than dependency or setup events', function (): void {
        $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $scripts = $manifest['scripts'];
        $expand = function (string $name) use (&$expand, $scripts): array {
            $commands = [];

            foreach ((array) ($scripts[$name] ?? []) as $command) {
                $commands[] = $command;

                if (str_starts_with($command, '@') && isset($scripts[mb_substr($command, 1)])) {
                    $commands = array_merge($commands, $expand(mb_substr($command, 1)));
                }
            }

            return $commands;
        };

        expect(implode("\n", $expand('post-create-project-cmd')))->toContain('bootstrap/finalize-project.php');

        foreach (['pre-install-cmd', 'post-install-cmd', 'pre-update-cmd', 'post-update-cmd', 'pre-autoload-dump', 'post-autoload-dump', 'post-root-package-install', 'setup'] as $event) {
            expect(implode("\n", $expand($event)))->not->toContain('finalize-project');
        }
    });

    it('records the source before replacing foundation release history without network or Git', function (): void {
        $directory = finalizationFixture();
        file_put_contents($directory->path('CHANGELOG.md'), '# Foundation history');
        file_put_contents($directory->path('.release-please-manifest.json'), '{".": "1.2.3"}');

        try {
            $process = finalizeFixture($directory);

            expect($process->isSuccessful())->toBeTrue($process->getErrorOutput())
                ->and(file_get_contents($directory->path('.foundation-version')))->toBe("jcf@1.2.3\n")
                ->and(file_get_contents($directory->path('version.txt')))->toBe("0.1.0\n")
                ->and(json_decode(file_get_contents($directory->path('.release-please-manifest.json')), true))->toBe(['.' => '0.1.0'])
                ->and(file_get_contents($directory->path('CHANGELOG.md')))->not->toContain('Foundation history');
        } finally {
            $directory->delete();
        }
    });

    it('preserves application releases and provenance on repeated creation hooks', function (): void {
        $directory = finalizationFixture();

        try {
            expect(finalizeFixture($directory)->isSuccessful())->toBeTrue();
            file_put_contents($directory->path('version.txt'), "2.4.0\n");
            file_put_contents($directory->path('.release-please-manifest.json'), '{".":"2.4.0"}');
            file_put_contents($directory->path('CHANGELOG.md'), '# Application releases');
            $directory->path('.git');

            expect(finalizeFixture($directory)->isSuccessful())->toBeTrue()
                ->and(file_get_contents($directory->path('.foundation-version')))->toBe("jcf@1.2.3\n")
                ->and(file_get_contents($directory->path('version.txt')))->toBe("2.4.0\n")
                ->and(file_get_contents($directory->path('.release-please-manifest.json')))->toBe('{".":"2.4.0"}')
                ->and(file_get_contents($directory->path('CHANGELOG.md')))->toBe('# Application releases');
        } finally {
            $directory->delete();
        }
    });

    it('rejects missing or malformed source versions before changing release files', function (string $version): void {
        $directory = finalizationFixture($version);

        try {
            expect(finalizeFixture($directory)->isSuccessful())->toBeFalse()
                ->and(file_get_contents($directory->path('version.txt')))->toBe($version)
                ->and(file_exists($directory->path('.foundation-version')))->toBeFalse()
                ->and(file_exists($directory->path('CHANGELOG.md')))->toBeFalse();
        } finally {
            $directory->delete();
        }
    })->with(['', 'dev-main', '01.2.3', "1.2.3\n1.2.4"]);

    it('refuses existing Git repositories and inherited release automation', function (string $file): void {
        $directory = finalizationFixture();
        file_put_contents($directory->path($file), '{}');

        try {
            expect(finalizeFixture($directory)->isSuccessful())->toBeFalse()
                ->and(file_get_contents($directory->path('version.txt')))->toBe("1.2.3\n")
                ->and(file_exists($directory->path('.foundation-version')))->toBeFalse();
        } finally {
            $directory->delete();
        }
    })->with(['.git', 'release-please-config.json']);

    it('rejects a blocked release destination before changing any file', function (): void {
        $directory = finalizationFixture();
        $directory->path('CHANGELOG.md/blocked');

        try {
            expect(finalizeFixture($directory)->isSuccessful())->toBeFalse()
                ->and(file_get_contents($directory->path('version.txt')))->toBe("1.2.3\n")
                ->and(file_exists($directory->path('.foundation-version')))->toBeFalse();
        } finally {
            $directory->delete();
        }
    });

    it('does not repair an invalid origin by resetting an existing application', function (): void {
        $directory = finalizationFixture();
        file_put_contents($directory->path('.foundation-version'), 'invalid');

        try {
            expect(finalizeFixture($directory)->isSuccessful())->toBeFalse()
                ->and(file_get_contents($directory->path('.foundation-version')))->toBe('invalid')
                ->and(file_get_contents($directory->path('version.txt')))->toBe("1.2.3\n");
        } finally {
            $directory->delete();
        }
    });
});
