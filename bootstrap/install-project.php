<?php

declare(strict_types=1);

/**
 * @param  list<string>  $command
 * @param  array<string, string>|null  $environment
 * @return array{code: int, output: string}
 */
function runJcfInstallerProcess(array $command, string $directory, ?array $environment = null, bool $capture = false): array
{
    $process = proc_open($command, [STDIN, $capture ? ['pipe', 'w'] : STDOUT, STDERR], $pipes, $directory, $environment);

    if (! is_resource($process)) {
        throw new RuntimeException('Unable to start Laravel Installer.');
    }

    $output = $capture ? stream_get_contents($pipes[1]) : '';
    if ($capture) {
        fclose($pipes[1]);
    }

    return ['code' => proc_close($process), 'output' => (string) $output];
}

/** @return array<string, string> */
function jcfInstallerFileHashes(string $directory): array
{
    $hashes = [];
    foreach (['.env', '.env.example', 'composer.json', 'composer.lock', 'package.json', 'pnpm-lock.yaml', '.foundation-version', 'version.txt', 'release-please-config.json', '.release-please-manifest.json', 'CHANGELOG.md'] as $file) {
        $path = $directory.'/'.$file;
        $hash = is_file($path) && ! is_link($path) ? hash_file('sha256', $path) : false;
        if ($hash === false) {
            throw new RuntimeException('Missing or unsafe installation file: '.$file);
        }
        $hashes[$file] = $hash;
    }

    return $hashes;
}

/** @return array{directory: string, nonce: string, prepared?: array<string, string>} */
function readJcfInstallerContext(string $path): array
{
    if ($path === '' || ! is_file($path) || is_link($path)) {
        throw new RuntimeException('Use bootstrap/install-project.php to enter the validated current-directory Installer path.');
    }

    $context = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    if (! is_array($context) || ! isset($context['directory'], $context['nonce']) || ! is_string($context['directory']) || ! is_string($context['nonce'])) {
        throw new RuntimeException('Invalid Installer context.');
    }

    return $context;
}

function prepareJcfInstaller(): int
{
    $path = (string) getenv('JCF_INSTALLER_CONTEXT');
    $context = readJcfInstallerContext($path);
    if (! hash_equals($context['nonce'], (string) getenv('JCF_INSTALLER_NONCE')) || $context['directory'] !== getcwd() || getenv('LARAVEL_INSTALLER_NO_NODE') !== '1') {
        throw new RuntimeException('Installer context mismatch; database and frontend automation remain disabled.');
    }

    $result = runJcfInstallerProcess([PHP_BINARY, 'artisan', 'init:prepare', '--no-interaction'], $context['directory']);
    if ($result['code'] !== 0) {
        return $result['code'];
    }

    $context['prepared'] = jcfInstallerFileHashes($context['directory']);
    if (file_put_contents($path, json_encode($context, JSON_THROW_ON_ERROR)) === false) {
        throw new RuntimeException('Unable to record Installer preparation.');
    }

    return 0;
}

function loadJcfInstaller(): void
{
    $autoload = dirname(__DIR__).'/vendor/autoload.php';
    if (! is_file($autoload)) {
        throw new RuntimeException('Run composer install with development dependencies in the source checkout before using this adapter.');
    }
    require_once $autoload;
    if (! Composer\InstalledVersions::isInstalled('laravel/installer') || mb_ltrim((string) Composer\InstalledVersions::getPrettyVersion('laravel/installer'), 'v') !== '5.32.0') {
        throw new RuntimeException('This path requires the tested Laravel Installer 5.32.0.');
    }
}

function runJcfCurrentDirectoryInstaller(string $package, bool $git): int
{
    $command = new class extends Laravel\Installer\Console\NewCommand
    {
        protected function verifyApplicationDoesntExist(mixed $directory): void
        {
            if ($directory === '.' && scandir('.') === ['.', '..']) {
                return;
            }
            parent::verifyApplicationDoesntExist($directory);
        }
    };
    $application = new Symfony\Component\Console\Application('Laravel Installer', '5.32.0');
    $application->setAutoExit(false);
    $application->addCommand($command);
    $input = new Symfony\Component\Console\Input\ArrayInput([
        'command' => 'new', 'name' => '.', '--using' => $package,
        '--no-node' => true, '--no-boost' => true, '--no-interaction' => true, '--git' => $git,
    ]);
    $input->setInteractive(false);

    return $application->run($input);
}

/** @param list<string> $arguments */
function installJcfProject(array $arguments): int
{
    $name = array_shift($arguments);
    if ($name === null || $name === '' || str_starts_with($name, '-') || in_array(basename($name), ['.', '..'], true)) {
        throw new RuntimeException('Usage: php bootstrap/install-project.php <new-directory> [--using=vendor/package:version] [--git]');
    }

    $package = 'tomylana93/jcf';
    $git = false;
    foreach ($arguments as $argument) {
        if ($argument === '--git') {
            $git = true;
        } elseif (str_starts_with($argument, '--using=')) {
            $package = mb_substr($argument, 8);
        } else {
            throw new RuntimeException('Unsupported Installer option: '.$argument);
        }
    }
    if (! preg_match('/^[a-z0-9_.-]+\/[a-z0-9_.-]+(?::[a-zA-Z0-9.*^~+-]+)?$/D', $package)) {
        throw new RuntimeException('Use a Composer package name with an optional version, not a repository URL.');
    }

    $parent = realpath(dirname($name));
    if ($parent === false || file_exists($name) || is_link($name)) {
        throw new RuntimeException('Target must be a new directory with an existing parent. Existing files are never replaced.');
    }
    $directory = $parent.'/'.basename($name);
    loadJcfInstaller();
    if (! mkdir($directory, 0755)) {
        throw new RuntimeException('Unable to create installation directory.');
    }

    $path = tempnam(sys_get_temp_dir(), 'jcf-installer-');
    if ($path === false) {
        throw new RuntimeException('Unable to create Installer context.');
    }

    $originalDirectory = getcwd();
    $originalContext = getenv('JCF_INSTALLER_CONTEXT');
    $originalNonce = getenv('JCF_INSTALLER_NONCE');
    $originalServer = array_intersect_key($_SERVER, array_flip(['JCF_INSTALLER_CONTEXT', 'JCF_INSTALLER_NONCE']));
    $originalEnvironment = array_intersect_key($_ENV, array_flip(['JCF_INSTALLER_CONTEXT', 'JCF_INSTALLER_NONCE']));
    try {
        $nonce = bin2hex(random_bytes(32));
        if (! chmod($path, 0600) || file_put_contents($path, json_encode(['directory' => $directory, 'nonce' => $nonce], JSON_THROW_ON_ERROR)) === false) {
            throw new RuntimeException('Unable to prepare private Installer context.');
        }
        putenv('JCF_INSTALLER_CONTEXT='.$path);
        putenv('JCF_INSTALLER_NONCE='.$nonce);
        $_SERVER['JCF_INSTALLER_CONTEXT'] = $_ENV['JCF_INSTALLER_CONTEXT'] = $path;
        $_SERVER['JCF_INSTALLER_NONCE'] = $_ENV['JCF_INSTALLER_NONCE'] = $nonce;
        if (! chdir($directory)) {
            throw new RuntimeException('Unable to enter installation directory.');
        }
        $code = runJcfCurrentDirectoryInstaller($package, $git);
        if ($code !== 0) {
            return $code;
        }

        $context = readJcfInstallerContext($path);
        if (! isset($context['prepared']) || $context['prepared'] !== jcfInstallerFileHashes($directory) || file_exists($directory.'/database/database.sqlite') || file_exists($directory.'/node_modules')) {
            throw new RuntimeException('Installer did not preserve the prepared scaffold; inspect the target before setup.');
        }
        if ($git && runJcfInstallerProcess(['git', 'rev-parse', '--verify', 'HEAD'], $directory, capture: true)['code'] !== 0) {
            throw new RuntimeException('Installer did not create the requested initial Git commit.');
        }
        fwrite(STDOUT, "Scaffold installed without database or frontend automation. Configure separate PostgreSQL databases, application identity and administrator credentials, then run composer setup in the new directory.\n");

        return 0;
    } finally {
        if ($originalDirectory !== false) {
            chdir($originalDirectory);
        }
        putenv($originalContext === false ? 'JCF_INSTALLER_CONTEXT' : 'JCF_INSTALLER_CONTEXT='.$originalContext);
        putenv($originalNonce === false ? 'JCF_INSTALLER_NONCE' : 'JCF_INSTALLER_NONCE='.$originalNonce);
        unset($_SERVER['JCF_INSTALLER_CONTEXT'], $_SERVER['JCF_INSTALLER_NONCE'], $_ENV['JCF_INSTALLER_CONTEXT'], $_ENV['JCF_INSTALLER_NONCE']);
        $_SERVER = array_replace($_SERVER, $originalServer);
        $_ENV = array_replace($_ENV, $originalEnvironment);
        unlink($path);
    }
}

try {
    $argv = [];
    foreach ($_SERVER['argv'] ?? [] as $argument) {
        if (! is_string($argument)) {
            throw new RuntimeException('Installer arguments must be strings.');
        }
        $argv[] = $argument;
    }
    $argv = array_values(array_filter($argv, static fn (string $argument): bool => ! in_array($argument, ['--ansi', '--no-ansi'], true)));
    exit(($argv[1] ?? '') === '--prepare' && count($argv) === 2
        ? prepareJcfInstaller()
        : installJcfProject(array_slice($argv, 1)));
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(1);
}
