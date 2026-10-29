<?php

declare(strict_types=1);

use Spatie\TemporaryDirectory\TemporaryDirectory;
use Symfony\Component\Process\Process;

function installerBridgeFixture(): TemporaryDirectory
{
    $directory = (new TemporaryDirectory)->create();
    mkdir($directory->path().'/bin');
    file_put_contents($directory->path().'/bin/composer', '#!'.PHP_BINARY."\n".<<<'PHP_WRAP'
    <?php
    file_put_contents(dirname(getcwd()).'/invocation.json', json_encode(['args' => array_slice($argv, 1), 'context' => getenv('JCF_INSTALLER_CONTEXT')]));
    if (getenv('FIXTURE_INSTALLER_EXIT')) {
        exit((int) getenv('FIXTURE_INSTALLER_EXIT'));
    }
    if (($argv[1] ?? '') === 'create-project') {
        foreach (['.env', '.env.example', 'composer.lock', 'package.json', 'pnpm-lock.yaml', '.foundation-version', 'version.txt', 'release-please-config.json', '.release-please-manifest.json', 'CHANGELOG.md'] as $file) {
            file_put_contents($file, 'prepared '.$file);
        }
        mkdir('bootstrap');
        copy(getenv('FIXTURE_BRIDGE'), 'bootstrap/install-project.php');
        file_put_contents('composer.json', json_encode(['require' => ['laravel/framework' => '^13.0'], 'extra' => ['laravel' => ['installer' => ['post-create-project' => ['@php bootstrap/install-project.php --prepare']]]]]));
        file_put_contents('artisan', '<?php if (($argv[1] ?? "") === "init:prepare") { exit((int) getenv("FIXTURE_PREPARE_EXIT")); }');
    }
    PHP_WRAP);
    file_put_contents($directory->path().'/bin/git', '#!'.PHP_BINARY."\n".<<<'PHP_WRAP'
    <?php
    if (($argv[1] ?? '') === 'config') { echo 'main'; }
    if (($argv[1] ?? '') === 'rev-parse') { echo str_repeat('1', 40); }
    if (($argv[1] ?? '') === 'branch' && getenv('FIXTURE_INSTALLER_MUTATE')) {
        file_put_contents('.env', 'unexpected rewrite');
    }
    PHP_WRAP);
    chmod($directory->path().'/bin/composer', 0755);
    chmod($directory->path().'/bin/git', 0755);

    return $directory;
}

/**
 * @param  list<string>  $arguments
 * @param  array<string, string>  $environment
 */
function runInstallerBridge(TemporaryDirectory $directory, array $arguments = [], array $environment = []): Process
{
    $script = dirname(__DIR__, 2).'/bootstrap/install-project.php';
    $process = new Process([PHP_BINARY, $script, ...$arguments], $directory->path(), [
        'PATH' => $directory->path().'/bin'.PATH_SEPARATOR.getenv('PATH'),
        'FIXTURE_BRIDGE' => $script,
        'COMPOSER_BINARY' => false,
        'https_proxy' => 'http://127.0.0.1:1',
        'http_proxy' => 'http://127.0.0.1:1',
        'JCF_INSTALLER_CONTEXT' => '',
        'JCF_INSTALLER_NONCE' => '',
        ...$environment,
    ]);
    $process->run();

    return $process;
}

describe('Validated Laravel Installer bridge', function (): void {
    it('uses current-directory no-node installation and preserves the scaffold', function (): void {
        $directory = installerBridgeFixture();

        try {
            $process = runInstallerBridge($directory, ['Application With Spaces', '--using=tomylana93/jcf:0.1.0', '--git']);
            $invocation = json_decode((string) file_get_contents($directory->path().'/invocation.json'), true);

            expect($process->isSuccessful())->toBeTrue($process->getOutput().$process->getErrorOutput())
                ->and($invocation['args'])->toContain('post-root-package-install')
                ->and(file_exists($invocation['context']))->toBeFalse()
                ->and(file_get_contents($directory->path().'/Application With Spaces/.env'))->toBe('prepared .env')
                ->and($process->getOutput())->toContain('Configure separate PostgreSQL databases');
        } finally {
            $directory->delete();
        }
    });

    it('refuses direct installer hooks without the bridge context', function (): void {
        $directory = installerBridgeFixture();

        try {
            $process = runInstallerBridge($directory, ['--prepare']);

            expect($process->getExitCode())->toBe(1)
                ->and($process->getErrorOutput())->toContain('validated current-directory Installer path')
                ->and(file_exists($directory->path().'/invocation.json'))->toBeFalse();
        } finally {
            $directory->delete();
        }
    });

    it('rejects unsafe installer options without overwriting existing destinations', function (array $arguments): void {
        $directory = installerBridgeFixture();
        file_put_contents($directory->path().'/existing', 'keep');

        try {
            $process = runInstallerBridge($directory, array_values(array_map(static fn (string $argument): string => $argument, $arguments)));

            expect($process->getExitCode())->toBe(1)
                ->and(file_get_contents($directory->path().'/existing'))->toBe('keep')
                ->and(file_exists($directory->path().'/invocation.json'))->toBeFalse();
        } finally {
            $directory->delete();
        }
    })->with([[['existing']], [['fresh', '--database=sqlite']], [['fresh', '--pnpm']], [['fresh', '--force']], [['fresh', '--github']], [['fresh', '--using=https://example.test/kit']], [['.']]]);

    it('requires source development dependencies before creating the destination', function (): void {
        $directory = installerBridgeFixture();
        mkdir($directory->path().'/standalone/bootstrap', 0755, true);
        copy(dirname(__DIR__, 2).'/bootstrap/install-project.php', $directory->path().'/standalone/bootstrap/install-project.php');

        try {
            $process = new Process([PHP_BINARY, 'standalone/bootstrap/install-project.php', 'fresh'], $directory->path());
            $process->run();

            expect($process->getExitCode())->toBe(1)
                ->and($process->getErrorOutput())->toContain('composer install with development dependencies')
                ->and($directory->path().'/fresh')->not->toBeDirectory();
        } finally {
            $directory->delete();
        }
    });

    it('propagates installer failure and removes its temporary context', function (): void {
        $directory = installerBridgeFixture();

        try {
            $process = runInstallerBridge($directory, ['fresh'], ['FIXTURE_INSTALLER_EXIT' => '23']);
            $invocation = json_decode((string) file_get_contents($directory->path().'/invocation.json'), true);

            expect($process->getExitCode())->toBe(23)
                ->and(file_exists($invocation['context']))->toBeFalse();
        } finally {
            $directory->delete();
        }
    });

    it('rejects invalid bridge context rather than entering automatic setup', function (string $nonce, string $noNode): void {
        $directory = installerBridgeFixture();
        $context = $directory->path().'/context.json';
        file_put_contents($context, json_encode(['directory' => $directory->path(), 'nonce' => 'expected']));

        try {
            $process = runInstallerBridge($directory, ['--prepare'], ['JCF_INSTALLER_CONTEXT' => $context, 'JCF_INSTALLER_NONCE' => $nonce, 'LARAVEL_INSTALLER_NO_NODE' => $noNode]);

            expect($process->getExitCode())->toBe(1)
                ->and($process->getErrorOutput())->toContain('context mismatch');
        } finally {
            $directory->delete();
        }
    })->with([['wrong', '1'], ['expected', '0']]);

    it('propagates required preparation failures rather than reporting installation success', function (): void {
        $directory = installerBridgeFixture();

        try {
            $process = runInstallerBridge($directory, ['fresh'], ['FIXTURE_PREPARE_EXIT' => '19']);

            expect($process->getExitCode())->toBe(19)
                ->and($process->getOutput())->not->toContain('Scaffold installed');
        } finally {
            $directory->delete();
        }
    });

    it('rejects an installer that rewrites prepared files after its hook', function (): void {
        $directory = installerBridgeFixture();

        try {
            $process = runInstallerBridge($directory, ['fresh', '--git'], ['FIXTURE_INSTALLER_MUTATE' => '1']);

            expect($process->getExitCode())->toBe(1)
                ->and($process->getErrorOutput())->toContain('did not preserve the prepared scaffold');
        } finally {
            $directory->delete();
        }
    });
});
