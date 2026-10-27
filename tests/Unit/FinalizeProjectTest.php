<?php

declare(strict_types=1);

use Spatie\TemporaryDirectory\TemporaryDirectory;
use Symfony\Component\Process\Process;

function finalizationFixture(string $version = "1.2.3\n", bool $includeReleaseWorkflow = true): TemporaryDirectory
{
    $directory = (new TemporaryDirectory)->create();
    copy(dirname(__DIR__, 2).'/bootstrap/finalize-project.php', $directory->path('bootstrap/finalize-project.php'));
    if ($includeReleaseWorkflow) {
        copy(dirname(__DIR__, 2).'/.github/workflows/release-please.yml', $directory->path('.github/workflows/release-please.yml'));
    }
    copy(dirname(__DIR__, 2).'/release-please-config.json', $directory->path('release-please-config.json'));
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
        $manifest = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
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
                ->and(json_decode((string) file_get_contents($directory->path('.release-please-manifest.json')), true))->toBe(['.' => '0.1.0'])
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
            file_put_contents($directory->path('release-please-config.json'), '{"packages":{".":{"release-type":"simple"}},"bootstrap-sha":"application-owned"}');
            file_put_contents($directory->path('.github/workflows/release-please.yml'), '# Application-owned release workflow');
            $directory->path('.git');

            expect(finalizeFixture($directory)->isSuccessful())->toBeTrue()
                ->and(file_get_contents($directory->path('.foundation-version')))->toBe("jcf@1.2.3\n")
                ->and(file_get_contents($directory->path('version.txt')))->toBe("2.4.0\n")
                ->and(file_get_contents($directory->path('.release-please-manifest.json')))->toBe('{".":"2.4.0"}')
                ->and(file_get_contents($directory->path('release-please-config.json')))->toContain('application-owned')
                ->and(file_get_contents($directory->path('.github/workflows/release-please.yml')))->toBe('# Application-owned release workflow')
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

    it('refuses existing Git repositories without provenance', function (string $file): void {
        $directory = finalizationFixture();
        file_put_contents($directory->path($file), '{}');

        try {
            expect(finalizeFixture($directory)->isSuccessful())->toBeFalse()
                ->and(file_get_contents($directory->path('version.txt')))->toBe("1.2.3\n")
                ->and(file_exists($directory->path('.foundation-version')))->toBeFalse();
        } finally {
            $directory->delete();
        }
    })->with(['.git']);

    it('replaces inherited release configuration with an independent root package', function (): void {
        $directory = finalizationFixture();
        file_put_contents($directory->path('release-please-config.json'), '{"bootstrap-sha":"foundation-history","release-as":"9.0.0","packages":{".":{"component":"jcf","initial-version":"1.0.0"}}}');
        $workflow = file_get_contents($directory->path('.github/workflows/release-please.yml'));

        try {
            expect(finalizeFixture($directory)->isSuccessful())->toBeTrue();

            $config = json_decode((string) file_get_contents($directory->path('release-please-config.json')), true, flags: JSON_THROW_ON_ERROR);

            expect(array_keys($config))->toBe(['$schema', 'packages'])
                ->and($config['packages'])->toBe(['.' => [
                    'release-type' => 'simple',
                    'version-file' => 'version.txt',
                    'include-component-in-tag' => false,
                    'include-v-in-tag' => true,
                    'bump-minor-pre-major' => false,
                    'bump-patch-for-minor-pre-major' => false,
                ]])
                ->and(file_get_contents($directory->path('.github/workflows/release-please.yml')))->toBe($workflow);
        } finally {
            $directory->delete();
        }
    });

    it('rejects an incomplete release workflow before changing identity', function (): void {
        $directory = finalizationFixture(includeReleaseWorkflow: false);

        try {
            expect(finalizeFixture($directory)->isSuccessful())->toBeFalse()
                ->and(file_get_contents($directory->path('version.txt')))->toBe("1.2.3\n")
                ->and(file_exists($directory->path('.foundation-version')))->toBeFalse();
        } finally {
            $directory->delete();
        }
    });

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
