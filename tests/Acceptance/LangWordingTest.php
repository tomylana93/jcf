<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Symfony\Component\Finder\Finder;

/**
 * Flattens every application translation file of a locale into dot-notation keys.
 *
 * @return array<string, string>
 */
function langWordingMessages(string $locale): array
{
    $files = Finder::create()->files()->in(dirname(__DIR__, 2).'/lang/'.$locale)->name('*.php')->notName('validation.php');
    $messages = [];

    foreach ($files as $file) {
        $messages += Arr::dot([$file->getBasename('.php') => require $file->getPathname()]);
    }

    return array_filter($messages, is_string(...));
}

/**
 * Lists the messages whose text matches a forbidden pattern, except allowlisted keys.
 *
 * @param  array<string, string>  $messages
 * @param  list<string>  $allowed
 * @return list<string>
 */
function langWordingViolations(array $messages, string $pattern, array $allowed = []): array
{
    $violations = [];

    foreach ($messages as $key => $text) {
        if (! in_array($key, $allowed, true) && preg_match($pattern, $text) === 1) {
            $violations[] = $key.': '.$text;
        }
    }

    return $violations;
}

describe('Translation wording', function (): void {
    it('omits filler words from messages', function (string $locale, string $pattern): void {
        $violations = langWordingViolations(langWordingMessages($locale), $pattern);

        expect($violations)->toBe([], "Remove filler wording:\n".implode("\n", $violations));
    })->with([
        'en please or successfully' => ['en', '/\b(please|successfully)\b/i'],
        'id silakan' => ['id', '/\bsilakan\b/iu'],
        'id berhasil as success filler' => ['id', '/\bberhasil (dihapus|disimpan|diperbarui|diunggah|di-upload|direset|dibuat)\b/iu'],
    ]);

    it('uses the fixed Indonesian technical terms', function (string $pattern): void {
        $violations = langWordingViolations(langWordingMessages('id'), $pattern);

        expect($violations)->toBe([], "Use the standard Indonesian term:\n".implode("\n", $violations));
    })->with([
        'upload and download instead of unggah and unduh' => ['/(unggah|unduh)/iu'],
        'pengguna instead of user' => ['/\b(tambah|edit|ubah) user\b|\bpermission user\b/iu'],
        'code instead of kode' => ['/\bkode\b/iu'],
    ]);

    it('describes impact instead of implementation details or local tooling', function (string $locale): void {
        /** The Mailpit hint renders only in the local environment; "queued" is accurate in the operator audit record. */
        $allowed = ['settings.mail.hint.mailpit', 'enums.audit_event.UserInvitationQueued'];

        $violations = langWordingViolations(
            langWordingMessages($locale),
            '/\b(mailpit|lerd|localhost|driver|probe|idempoten\w*|synchronous\w*|whoops|temporary|queued)\b/iu',
            $allowed,
        );

        expect($violations)->toBe([], "Describe the impact for the audience:\n".implode("\n", $violations));
    })->with(['en', 'id']);

    it('writes descriptions that add information beyond the title', function (string $locale, string $pattern): void {
        $descriptions = array_filter(langWordingMessages($locale), fn (string $key): bool => str_ends_with($key, '.description'), ARRAY_FILTER_USE_KEY);

        $violations = langWordingViolations($descriptions, $pattern);

        expect($violations)->toBe([], "Rewrite generic descriptions:\n".implode("\n", $violations));
    })->with([
        'en manage pattern' => ['en', '/^Manage [^,]+\.$/u'],
        'id kelola pattern' => ['id', '/^Kelola [^,]+\.$/u'],
    ]);

    it('writes titles and navigation labels in sentence case', function (string $locale): void {
        $titles = array_filter(
            langWordingMessages($locale),
            fn (string $key): bool => preg_match('/(^breadcrumb\.|^navigation\.link\.|(^|\.)(\w+_)?title$)/', $key) === 1,
            ARRAY_FILTER_USE_KEY,
        );
        $properNouns = ['API', 'SMTP', 'TOTP', '2FA', 'QR', 'HTML', 'URL', 'ZIP', 'PDF', 'Excel', 'Mailpit'];
        $violations = [];

        foreach ($titles as $key => $title) {
            foreach (array_slice(preg_split('/\s+/u', $title) ?: [], 1) as $word) {
                $word = mb_trim($word, '():,.');

                if (preg_match('/^\p{Lu}\p{Ll}/u', $word) === 1 && ! in_array($word, $properNouns, true)) {
                    $violations[] = $key.': '.$title;

                    break;
                }
            }
        }

        expect($violations)->toBe([], "Use sentence case:\n".implode("\n", $violations));
    })->with(['en', 'id']);
});
