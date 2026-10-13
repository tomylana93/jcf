<?php

declare(strict_types=1);

use App\Actions\Documentation\RenderEditorContent;
use Illuminate\Validation\ValidationException;

describe('Editor schema', function (): void {
    it('renders internal URLs and anchors in the same tab with escaped parameters', function (string $href): void {
        $text = ['type' => 'text', 'text' => 'Application page', 'marks' => [['type' => 'link', 'attrs' => ['href' => $href, 'target' => '_blank', 'title' => 'Guide "Application"']]]];
        $document = ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [$text]]]];

        $output = resolve(RenderEditorContent::class)->handle($document);

        expect($output['html'])->toContain('href="'.e($href).'"', 'target="_self"', 'title="Guide &quot;Application&quot;"', '>Application page</a>');
    })->with(['/dashboard', '/documentation?search=email&sort=title#help', '#section']);

    it('rejects malformed link titles', function (): void {
        $text = ['type' => 'text', 'text' => 'Guide', 'marks' => [['type' => 'link', 'attrs' => ['href' => '/documentation', 'title' => ['unsafe']]]]];

        expect(fn () => resolve(RenderEditorContent::class)->handle(['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [$text]]]]))->toThrow(ValidationException::class);
    });

    it('rejects internal-looking URLs that can become protocol-relative browser links', function (): void {
        $href = '/'.chr(92).'other.example/path';
        $text = ['type' => 'text', 'text' => 'Application page', 'marks' => [['type' => 'link', 'attrs' => ['href' => $href]]]];

        expect(fn () => resolve(RenderEditorContent::class)->handle(['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [$text]]]]))->toThrow(ValidationException::class);
    });

    it('renders tables with header cells, merged cells, alignment and escaped content', function (): void {
        $paragraph = ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Value <unsafe>']]];
        $document = ['type' => 'doc', 'content' => [['type' => 'table', 'content' => [
            ['type' => 'tableRow', 'content' => [['type' => 'tableHeader', 'attrs' => ['colspan' => 2, 'rowspan' => 2, 'colwidth' => [120, 180], 'align' => 'center'], 'content' => [$paragraph]], ['type' => 'tableCell', 'content' => [$paragraph]]]],
            ['type' => 'tableRow', 'content' => [['type' => 'tableCell', 'attrs' => ['colspan' => 1, 'rowspan' => 1, 'colwidth' => null, 'align' => null], 'content' => [['type' => 'paragraph']]]]],
        ]]]];

        $output = resolve(RenderEditorContent::class)->handle($document);

        expect($output['html'])->toContain('<table><tbody><tr>', '<th colspan="2" rowspan="2" colwidth="120,180" style="text-align: center">', '<td><p>Value &lt;unsafe&gt;</p></td>', '</tbody></table>')
            ->and($output['text'])->toContain('Value <unsafe>');
    });

    it('rejects malformed table cells and unsafe attributes', function (array $attrs): void {
        $document = ['type' => 'doc', 'content' => [['type' => 'table', 'content' => [['type' => 'tableRow', 'content' => [['type' => 'tableCell', 'attrs' => $attrs, 'content' => [['type' => 'paragraph']]]]]]]]];

        expect(fn () => resolve(RenderEditorContent::class)->handle($document))->toThrow(ValidationException::class);
    })->with([
        'unsafe attribute' => [['onclick' => 'alert(1)']],
        'invalid column span' => [['colspan' => 0]],
        'excessive row span' => [['rowspan' => 101]],
        'unsafe alignment' => [['align' => 'center; background:url(x)']],
        'non-array widths' => [['colwidth' => '120']],
        'width count mismatch' => [['colspan' => 2, 'colwidth' => [120]]],
        'unsafe width' => [['colwidth' => ['120; color:red']]],
    ]);

    it('rejects content exceeding encoding depth, invalid UTF-8 and excessive nodes', function (): void {
        $renderer = resolve(RenderEditorContent::class);
        $node = ['type' => 'paragraph'];
        for ($i = 0; $i < 70; $i++) {
            $node = ['type' => 'blockquote', 'content' => [$node]];
        }

        expect(fn () => $renderer->handle(['type' => 'doc', 'content' => [$node]]))->toThrow(ValidationException::class)
            ->and(fn () => $renderer->handle(['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => "\xFF"]]]]]))->toThrow(ValidationException::class)
            ->and(fn () => $renderer->handle(['type' => 'doc', 'content' => array_fill(0, 10_001, ['type' => 'paragraph'])]))->toThrow(ValidationException::class);
    });

    it('renders supported blocks and marks with escaped text and safe links', function (): void {
        $text = ['type' => 'text', 'text' => 'Example <code>', 'marks' => array_map(fn (string $type): array => ['type' => $type], ['bold', 'italic', 'strike', 'underline', 'code'])];
        $paragraph = ['type' => 'paragraph', 'content' => [$text, ['type' => 'hardBreak'], ['type' => 'text', 'text' => 'Link', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'https://example.com/?a=1&b=2']]]]]];
        $document = ['type' => 'doc', 'content' => [
            $paragraph,
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Heading']]],
            ['type' => 'bulletList', 'content' => [['type' => 'listItem', 'content' => [$paragraph]]]],
            ['type' => 'orderedList', 'attrs' => ['start' => 3, 'type' => null], 'content' => [['type' => 'listItem', 'content' => [$paragraph]]]],
            ['type' => 'blockquote', 'content' => [$paragraph]],
            ['type' => 'codeBlock', 'attrs' => ['language' => 'php'], 'content' => [['type' => 'text', 'text' => '<?php echo "Hi";']]],
            ['type' => 'horizontalRule'],
        ]];

        $output = resolve(RenderEditorContent::class)->handle($document);

        expect($output['html'])->toContain('<strong>', '<em>', '<u>', '<s>', '<code>', '<br>', '<h2>Heading</h2>', '<ul>', '<ol start="3">', '<blockquote>', '<pre>', '<hr>', '&lt;code&gt;', 'rel="noopener noreferrer nofollow"')
            ->and($output['text'])->toContain('Heading', 'Example <code>', '<?php echo "Hi";')->not->toContain('<strong>');
    });

    it('rejects malformed nodes', function (array $node): void {
        expect(fn () => resolve(RenderEditorContent::class)->handle(['type' => 'doc', 'content' => [$node]]))->toThrow(ValidationException::class);
    })->with([
        'unsupported' => [['type' => 'iframe']],
        'empty table' => [['type' => 'table']],
        'empty cell' => [['type' => 'table', 'content' => [['type' => 'tableRow', 'content' => [['type' => 'tableCell']]]]]],
        'unknown key' => [['type' => 'paragraph', 'onclick' => 'evil()']],
        'unknown attribute' => [['type' => 'paragraph', 'attrs' => ['style' => 'color:red']]],
        'children type' => [['type' => 'paragraph', 'content' => 'text']],
        'attrs type' => [['type' => 'paragraph', 'attrs' => 'text']],
        'scalar child' => [['type' => 'paragraph', 'content' => ['text']]],
        'nesting' => [['type' => 'paragraph', 'content' => [['type' => 'paragraph']]]],
        'empty quote' => [['type' => 'blockquote']],
        'list item' => [['type' => 'bulletList', 'content' => [['type' => 'listItem', 'content' => [['type' => 'heading', 'attrs' => ['level' => 2]]]]]]],
        'block text' => [['type' => 'paragraph', 'text' => 'bad']],
        'block marks' => [['type' => 'paragraph', 'marks' => []]],
        'heading' => [['type' => 'heading', 'attrs' => ['level' => 1]]],
        'list start' => [['type' => 'orderedList', 'attrs' => ['start' => 0], 'content' => [['type' => 'listItem', 'content' => [['type' => 'paragraph']]]]]],
        'language' => [['type' => 'codeBlock', 'attrs' => ['language' => '<script>']]],
        'code marks' => [['type' => 'codeBlock', 'content' => [['type' => 'text', 'text' => 'x', 'marks' => []]]]],
        'text' => [['type' => 'paragraph', 'content' => [['type' => 'text']]]],
        'marks type' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'x', 'marks' => 'bold']]]],
        'mark type' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'x', 'marks' => [null]]]]],
        'unknown mark' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'x', 'marks' => [['type' => 'script']]]]]],
        'duplicate mark' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'x', 'marks' => [['type' => 'bold'], ['type' => 'bold']]]]]],
        'mark attrs' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'x', 'marks' => [['type' => 'bold', 'attrs' => ['style' => 'x']]]]]]],
        'unsafe URL' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'x', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]]]]]],
    ]);

    it('rejects oversized and deeply nested documents', function (): void {
        expect(fn () => resolve(RenderEditorContent::class)->handle(['type' => 'paragraph']))->toThrow(ValidationException::class)
            ->and(fn () => resolve(RenderEditorContent::class)->handle(['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => str_repeat('x', 500_001)]]]]]))->toThrow(ValidationException::class);

        $node = ['type' => 'paragraph'];
        for ($i = 0; $i < 31; $i++) {
            $node = ['type' => 'blockquote', 'content' => [$node]];
        }

        expect(fn () => resolve(RenderEditorContent::class)->handle(['type' => 'doc', 'content' => [$node]]))->toThrow(ValidationException::class);
    });
});
