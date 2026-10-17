<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Actions\Documentation\RenderEditorContent;
use App\Models\WebsiteContent;
use Closure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class ValidateWebsiteContent
{
    public function __construct(private RenderEditorContent $renderContent) {}

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function handle(array $data, WebsiteContent $content, bool $article): array
    {
        $rules = [
            'content' => ['required', 'array:title,slug,excerpt,body_json,sections,seo,cover'],
            'content.title' => ['required', 'string', 'max:255'],
            'content.slug' => [$article ? 'required' : 'prohibited', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'content.excerpt' => ['present', 'string', 'max:2000'],
            'content.body_json' => ['required', 'array'],
            'content.sections' => ['present', 'array', 'list', 'max:20'],
            'content.sections.*.id' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/', 'distinct:strict'],
            'content.sections.*.type' => ['required', Rule::in(['hero', 'introduction', 'services', 'strengths', 'articles', 'contact'])],
            'content.sections.*.visible' => ['required', 'boolean'],
            'content.sections.*.title' => ['required', 'string', 'max:255'],
            'content.seo' => ['present', 'array:title,description,robots,image'],
            'content.seo.title' => ['sometimes', 'string', 'max:255'],
            'content.seo.description' => ['sometimes', 'string', 'max:500'],
            'content.seo.robots' => ['sometimes', Rule::in(['index,follow', 'noindex,nofollow'])],
            'content.seo.image' => ['sometimes', 'nullable', 'array:media_id,alt'],
            'content.cover' => ['present', 'nullable', 'array:media_id,alt'],
        ];
        $validator = Validator::make(['content' => $data], $rules);
        $validated = $validator->validate()['content'];
        foreach ($validated['sections'] as $index => $section) {
            $fields = match ($section['type']) {
                'hero' => ['description' => ['present', 'string', 'max:2000'], 'image' => ['present', 'nullable', 'array:media_id,alt'], 'cta' => ['present', 'nullable', 'array:label,href']],
                'introduction' => ['body_json' => ['required', 'array']],
                'services', 'strengths' => ['items' => ['required', 'array', 'list', 'max:20'], 'items.*' => ['array:title,description'], 'items.*.title' => ['required', 'string', 'max:255'], 'items.*.description' => ['present', 'string', 'max:2000']],
                'articles' => ['featured' => ['required', 'boolean'], 'limit' => ['required', 'integer', 'min:1', 'max:12']],
                default => throw ValidationException::withMessages(['sections' => __('validation.in', ['attribute' => 'type'])]),
                'contact' => ['description' => ['present', 'string', 'max:2000'], 'cta' => ['required', 'array:label,href']],
            };
            $keys = array_unique(array_map(static fn (string $key): string => explode('.', $key)[0], array_keys($fields)));
            Validator::make(['section' => $section], [
                'section' => ['array:'.implode(',', ['id', 'type', 'visible', 'title', ...$keys])],
                ...array_combine(array_map(static fn (string $key): string => 'section.'.$key, array_keys($fields)), array_values($fields)),
            ])->validate();
            if (isset($section['cta'])) {
                $this->link($section['cta']);
            }
            if (isset($section['image'])) {
                $validated['sections'][$index]['image'] = $this->image($section['image'], $content);
            }
            $validated['sections'][$index]['visible'] = (bool) $section['visible'];
            if ($section['type'] === 'articles') {
                $validated['sections'][$index]['featured'] = (bool) $section['featured'];
                $validated['sections'][$index]['limit'] = (int) $section['limit'];
            }
            if ($section['type'] === 'introduction') {
                $validated['sections'][$index]['body'] = $this->renderContent->handle($section['body_json'])['html'];
            }
        }
        if ($validated['cover'] !== null) {
            $validated['cover'] = $this->image($validated['cover'], $content);
        }
        if (isset($validated['seo']['image'])) {
            $validated['seo']['image'] = $this->image($validated['seo']['image'], $content);
        }
        $rendered = $this->renderContent->handle($validated['body_json']);

        return [...$validated, 'body_html' => $rendered['html'], 'body_text' => $rendered['text']];
    }

    /** @param array<string, mixed> $link */
    public function link(array $link): void
    {
        Validator::make($link, ['label' => ['required', 'string', 'max:100'], 'href' => ['required', 'string', 'max:2000', $this->urlRule()]])->validate();
    }

    public function urlRule(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || preg_match('/[\x00-\x20\x7f\\\\]/', $value) || ! preg_match('~^(https?://[^/]+|/(?!/)|mailto:|tel:|\#)~i', $value)) {
                $fail(__('validation.url', ['attribute' => $attribute]));
            }
        };
    }

    /** @param array<string, mixed> $image
     * @return array{media_id: int, alt: string}
     */
    private function image(array $image, WebsiteContent $content): array
    {
        Validator::make($image, [
            'media_id' => ['required', 'integer', Rule::exists('media', 'id')->where('model_type', (new WebsiteContent)->getMorphClass())->where('model_id', $content->id)->where('collection_name', 'website-images')],
            'alt' => ['required', 'string', 'max:255'],
        ])->validate();

        return ['media_id' => (int) $image['media_id'], 'alt' => $image['alt']];
    }
}
