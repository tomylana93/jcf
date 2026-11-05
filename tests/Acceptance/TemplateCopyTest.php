<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/**
 * Lists user-facing copy written as literal text in a Vue template.
 *
 * @return list<string>
 */
function hardcodedTemplateCopy(string $source): array
{
    $start = mb_strpos($source, '<template');
    $end = mb_strrpos($source, '</template>');

    if ($start === false || $end === false) {
        return [];
    }

    $template = (string) preg_replace('/<!--.*?-->/s', '', mb_substr($source, $start, $end - $start));
    $findings = [];

    preg_match_all('/(?<=\s)(title|placeholder|aria-label|alt)="([^"]*\p{L}[^"]*)"/u', $template, $attributes, PREG_SET_ORDER);

    foreach ($attributes as [, $attribute, $value]) {
        $findings[] = sprintf('%s="%s"', $attribute, $value);
    }

    $text = (string) preg_replace(
        ['/<\/?[A-Za-z][^>"\']*(?:(?:"[^"]*"|\'[^\']*\')[^>"\']*)*\/?>/', '/\{\{.*?\}\}/s', '/&[#\w]+;/'],
        ["\n", ' ', ' '],
        $template,
    );

    foreach (preg_split('/\n/', $text) ?: [] as $line) {
        if (preg_match('/\p{L}/u', $line) === 1) {
            $findings[] = mb_trim($line);
        }
    }

    return $findings;
}

describe('Vue template copy', function (): void {
    it('reads user-facing text from translations instead of literal template text', function (): void {
        $files = Finder::create()
            ->files()
            ->in(dirname(__DIR__, 2).'/resources/js')
            ->exclude('components/ui')
            ->name('*.vue');

        $violations = [];

        foreach ($files as $file) {
            foreach (hardcodedTemplateCopy($file->getContents()) as $copy) {
                $violations[] = $file->getRelativePathname().': '.$copy;
            }
        }

        expect($violations)->toBe([], "Use t() for template copy:\n".implode("\n", $violations));
    });
});
