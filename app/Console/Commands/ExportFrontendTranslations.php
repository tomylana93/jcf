<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('lang:export {--output= : Destination JSON file}')]
#[Description('Export PHP language messages for Vue I18n')]
final class ExportFrontendTranslations extends Command
{
    public function handle(): int
    {
        $messages = [];

        foreach (File::directories(lang_path()) as $directory) {
            $locale = basename($directory);
            $messages[$locale] = [];

            foreach (File::files($directory) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                /** @var array<string, mixed> $translations */
                $translations = require $file->getPathname();
                $messages[$locale][$file->getBasename('.php')] = $this->convertMessages($translations);
            }
        }

        $output = $this->option('output') ?? resource_path('js/lang/messages.json');
        File::ensureDirectoryExists(dirname($output));
        File::put($output, json_encode($messages, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);

        $this->components->info('Frontend language messages exported.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $messages
     * @return array<string, mixed>
     */
    private function convertMessages(array $messages): array
    {
        foreach ($messages as $key => $message) {
            $messages[$key] = is_array($message)
                ? $this->convertMessages($message)
                : preg_replace_callback('/:[a-zA-Z_][a-zA-Z0-9_]*|[{}@|]/', static fn (array $match): string => str_starts_with($match[0], ':')
                    ? '{'.mb_substr($match[0], 1).'}'
                    : "{'".$match[0]."'}", $message);
        }

        return $messages;
    }
}
