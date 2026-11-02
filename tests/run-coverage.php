<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

require __DIR__.'/../vendor/autoload.php';

$usingPcov = extension_loaded('pcov');

$phpArguments = match (true) {
    $usingPcov => ['-d', 'pcov.enabled=1'],
    extension_loaded('xdebug') => [],
    default => ['-d', 'zend_extension=xdebug'],
};

$environment = $usingPcov
    ? ['XDEBUG_MODE' => 'off']
    : ['XDEBUG_MODE' => 'coverage'];

$process = new Process([
    PHP_BINARY,
    ...$phpArguments,
    __DIR__.'/../vendor/bin/pest',
    '--parallel',
    '--exclude-testsuite=Browser',
    '--passthru-php='.implode(' ', $phpArguments),
    '--coverage',
    '--exactly=100.0',
    ...array_slice($argv, 1),
], dirname(__DIR__), $environment, timeout: null);

exit($process->run(function (string $type, string $buffer): void {
    fwrite($type === Process::ERR ? STDERR : STDOUT, $buffer);
}));
