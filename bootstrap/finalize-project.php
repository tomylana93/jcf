<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$marker = $root.'/.foundation-version';
$versionPattern = '/\A(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?\z/';

if (file_exists($marker)) {
    $origin = mb_trim((string) file_get_contents($marker));

    if (! str_starts_with($origin, 'jcf@') || ! preg_match($versionPattern, mb_substr($origin, 4))) {
        fwrite(STDERR, "Invalid .foundation-version; restore the original marker before continuing.\n");
        exit(1);
    }

    fwrite(STDOUT, "Application already finalized; versions and changelog preserved.\n");
    exit(0);
}

if (file_exists($root.'/.git')) {
    fwrite(STDERR, "Refusing to reset an existing repository. Finalize a fresh source archive before git init.\n");
    exit(1);
}

$source = mb_trim((string) @file_get_contents($root.'/version.txt'));

if (! preg_match($versionPattern, $source)) {
    fwrite(STDERR, "Missing or invalid source version.txt; use a versioned JCF archive.\n");
    exit(1);
}

if (file_exists($root.'/release-please-config.json') || file_exists($root.'/.github/workflows/release-please.yml')) {
    fwrite(STDERR, "Release configuration requires the derived application template before finalization.\n");
    exit(1);
}

$files = [
    'version.txt' => "0.1.0\n",
    '.release-please-manifest.json' => "{\n    \".\": \"0.1.0\"\n}\n",
    'CHANGELOG.md' => "# Changelog\n\nApplication release history starts here.\n",
    '.foundation-version' => 'jcf@'.$source."\n",
];
foreach (array_keys($files) as $name) {
    $path = $root.'/'.$name;

    if (is_link($path) || (file_exists($path) && ! is_file($path))) {
        fwrite(STDERR, 'Refusing non-regular release file: '.$name."\n");
        exit(1);
    }
}

$originals = [];

try {
    foreach ($files as $name => $contents) {
        $path = $root.'/'.$name;
        $originals[$name] = file_exists($path) ? file_get_contents($path) : null;

        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write '.$name);
        }
    }
} catch (Throwable $exception) {
    foreach ($originals as $name => $contents) {
        if ($contents === null) {
            if (file_exists($root.'/'.$name)) {
                unlink($root.'/'.$name);
            }
        } else {
            file_put_contents($root.'/'.$name, $contents, LOCK_EX);
        }
    }

    fwrite(STDERR, 'Finalization failed: '.$exception->getMessage()."\n");
    exit(1);
}

fwrite(STDOUT, "Application finalized at 0.1.0; foundation origin recorded. Initialize a new Git repository for this application.\n");
