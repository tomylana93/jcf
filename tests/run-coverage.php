<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

require __DIR__.'/../vendor/autoload.php';

$phpArguments = ['-d', 'memory_limit=512M'];
if (! extension_loaded('xdebug')) {
    $phpArguments = [...$phpArguments, '-d', 'zend_extension=xdebug'];
}
$process = new Process([
    PHP_BINARY,
    ...$phpArguments,
    __DIR__.'/../vendor/bin/pest',
    '--parallel',
    '--processes=2',
    '--exclude-testsuite=Browser',
    '--passthru-php='.implode(' ', $phpArguments),
    '--coverage',
    '--exactly=100.0',
    ...array_slice($argv, 1),
], dirname(__DIR__), ['XDEBUG_MODE' => 'coverage'], timeout: null);

exit($process->run(function (string $type, string $buffer): void {
    fwrite($type === Process::ERR ? STDERR : STDOUT, $buffer);
}));
