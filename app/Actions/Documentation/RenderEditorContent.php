<?php

declare(strict_types=1);

namespace App\Actions\Documentation;

use Illuminate\Validation\ValidationException;
use JsonException;

final class RenderEditorContent
{
    /**
     * @param  array<string, mixed>  $document
     * @return array{html: string, text: string}
     */
    public function handle(array $document): array
    {
        try {
            $encoded = json_encode($document, JSON_THROW_ON_ERROR, 64);
        } catch (JsonException) {
            $this->invalid();
        }
        if (($document['type'] ?? null) !== 'doc' || mb_strlen($encoded, '8bit') > 500_000) {
            $this->invalid();
        }
        $count = 0;
        $html = $this->node($document, 0, $count);
        $text = html_entity_decode(strip_tags(str_replace(['</p>', '</h2>', '</h3>', '</h4>', '</li>', '</pre>', '<br>', '<hr>'], ' ', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return ['html' => $html, 'text' => mb_trim($text)];
    }

    /** @param array<string, mixed> $node */
    private function node(array $node, int $depth, int &$count): string
    {
        if ($depth > 30 || ++$count > 10_000 || array_diff(array_keys($node), ['type', 'content', 'attrs', 'text', 'marks']) !== []) {
            $this->invalid();
        }
        $type = $node['type'] ?? null;
        $children = $node['content'] ?? [];
        $attrs = $node['attrs'] ?? [];
        if (! is_array($children) || ! array_is_list($children) || ! is_array($attrs)) {
            $this->invalid();
        }
        $blocks = ['paragraph', 'heading', 'bulletList', 'orderedList', 'blockquote', 'codeBlock', 'horizontalRule', 'table'];
        $allowed = match ($type) {
            'doc', 'blockquote' => $blocks,
            'listItem' => ['paragraph', ...$blocks],
            'bulletList', 'orderedList' => ['listItem'],
            'paragraph', 'heading' => ['text', 'hardBreak'],
            'codeBlock' => ['text'],
            'table' => ['tableRow'],
            'tableRow' => ['tableCell', 'tableHeader'],
            'tableCell', 'tableHeader' => $blocks,
            'text', 'hardBreak', 'horizontalRule' => [],
            default => $this->invalid(),
        };
        if (in_array($type, ['doc', 'blockquote', 'listItem', 'bulletList', 'orderedList', 'table', 'tableCell', 'tableHeader'], true) && $children === []) {
            $this->invalid();
        }
        if ($type === 'listItem' && ($children[0]['type'] ?? null) !== 'paragraph') {
            $this->invalid();
        }
        $allowedAttrs = match ($type) {
            'heading' => ['level'],
            'orderedList' => ['start', 'type'],
            'codeBlock' => ['language'],
            'tableCell', 'tableHeader' => ['colspan', 'rowspan', 'colwidth', 'align'],
            default => [],
        };
        if (array_diff(array_keys($attrs), $allowedAttrs) !== [] || ($type !== 'text' && isset($node['text'])) || (! in_array($type, ['text', 'hardBreak'], true) && isset($node['marks']))) {
            $this->invalid();
        }
        $body = '';
        foreach ($children as $child) {
            if (! is_array($child) || ($type === 'codeBlock' && isset($child['marks']))) {
                $this->invalid();
            }
            $childBody = $this->node($child, $depth + 1, $count);
            if (! in_array($child['type'] ?? null, $allowed, true)) {
                $this->invalid();
            }
            $body .= $childBody;
        }
        if ($type === 'text') {
            if (! is_string($node['text'] ?? null) || $node['text'] === '' || $children !== []) {
                $this->invalid();
            }
            $body = e($node['text']);
        }
        $body = $this->marks($body, $node['marks'] ?? []);
        if ($type === 'heading' && ! in_array($attrs['level'] ?? null, [2, 3, 4], true)) {
            $this->invalid();
        }
        $start = $attrs['start'] ?? 1;
        if ($type === 'orderedList' && (! is_int($start) || $start < 1 || $start > 1_000_000 || ($attrs['type'] ?? null) !== null)) {
            $this->invalid();
        }
        if ($type === 'codeBlock' && isset($attrs['language']) && (! is_string($attrs['language']) || ! preg_match('/^[a-zA-Z0-9_-]{1,50}$/', $attrs['language']))) {
            $this->invalid();
        }

        $cellAttributes = in_array($type, ['tableCell', 'tableHeader'], true) ? $this->cellAttributes($attrs) : '';

        return match ($type) {
            'doc', 'text' => $body,
            'paragraph' => '<p>'.$body.'</p>',
            'heading' => '<h'.$attrs['level'].'>'.$body.'</h'.$attrs['level'].'>',
            'bulletList' => '<ul>'.$body.'</ul>',
            'orderedList' => '<ol start="'.$start.'">'.$body.'</ol>',
            'listItem' => '<li>'.$body.'</li>',
            'blockquote' => '<blockquote>'.$body.'</blockquote>',
            'codeBlock' => '<pre><code>'.$body.'</code></pre>',
            'table' => '<table><tbody>'.$body.'</tbody></table>',
            'tableRow' => '<tr>'.$body.'</tr>',
            'tableCell' => '<td'.$cellAttributes.'>'.$body.'</td>',
            'tableHeader' => '<th'.$cellAttributes.'>'.$body.'</th>',
            'horizontalRule' => '<hr>',
            'hardBreak' => '<br>',
        };
    }

    /** @param array<string, mixed> $attrs */
    private function cellAttributes(array $attrs): string
    {
        $colspan = $attrs['colspan'] ?? 1;
        $rowspan = $attrs['rowspan'] ?? 1;
        $widths = $attrs['colwidth'] ?? null;
        $align = $attrs['align'] ?? null;
        if (! is_int($colspan) || $colspan < 1 || $colspan > 100 || ! is_int($rowspan) || $rowspan < 1 || $rowspan > 100 || ! in_array($align, [null, 'left', 'center', 'right'], true)) {
            $this->invalid();
        }
        $attributes = ($colspan > 1 ? ' colspan="'.$colspan.'"' : '').($rowspan > 1 ? ' rowspan="'.$rowspan.'"' : '');
        if ($widths !== null) {
            if (! is_array($widths) || ! array_is_list($widths) || count($widths) !== $colspan) {
                $this->invalid();
            }
            foreach ($widths as $width) {
                if (! is_int($width) || $width < 1 || $width > 2000) {
                    $this->invalid();
                }
            }
            $attributes .= ' colwidth="'.implode(',', $widths).'"';
        }
        if ($align !== null) {
            $attributes .= ' style="text-align: '.$align.'"';
        }

        return $attributes;
    }

    private function marks(string $body, mixed $marks): string
    {
        if (! is_array($marks) || ! array_is_list($marks) || count($marks) > 7) {
            $this->invalid();
        }
        $seen = [];
        foreach ($marks as $mark) {
            if (! is_array($mark) || array_diff(array_keys($mark), ['type', 'attrs']) !== []) {
                $this->invalid();
            }
            $type = $mark['type'] ?? null;
            if (! is_string($type) || in_array($type, $seen, true)) {
                $this->invalid();
            }
            $seen[] = $type;
            $tag = match ($type) {
                'bold' => 'strong', 'italic' => 'em', 'strike' => 's', 'underline' => 'u', 'code' => 'code', 'link' => 'a',
                default => $this->invalid(),
            };
            $attrs = $mark['attrs'] ?? [];
            if (! is_array($attrs) || ($type !== 'link' && $attrs !== [])) {
                $this->invalid();
            }
            $attributes = '';
            if ($type === 'link') {
                $href = $attrs['href'] ?? null;
                if (! is_string($href) || mb_strlen($href) > 2000 || str_contains($href, '\\') || preg_match('/[\x00-\x20\x7f]/', $href) || ! preg_match('~^(https?://|mailto:|/(?!/)|\#)~i', $href) || array_diff(array_keys($attrs), ['href', 'target', 'rel', 'class', 'title']) !== []) {
                    $this->invalid();
                }
                if (isset($attrs['title']) && (! is_string($attrs['title']) || mb_strlen($attrs['title']) > 255)) {
                    $this->invalid();
                }
                $target = str_starts_with($href, '/') || str_starts_with($href, '#') ? '_self' : '_blank';
                $attributes = ' href="'.e($href).'" target="'.$target.'" rel="noopener noreferrer nofollow"';
                if (isset($attrs['title'])) {
                    $attributes .= ' title="'.e($attrs['title']).'"';
                }
            }
            $body = '<'.$tag.$attributes.'>'.$body.'</'.$tag.'>';
        }

        return $body;
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['content_json' => __('documentation.error.invalid_content')]);
    }
}
