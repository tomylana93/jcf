<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Actions\Documentation\RenderEditorContent;
use App\Models\WebsiteContent;
use Closure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final readonly class ValidateWebsiteContent
{
    private const array SECTION_TYPES = ['hero', 'introduction', 'services', 'strengths', 'articles', 'contact'];

    public function __construct(private RenderEditorContent $renderContent) {}

    /**
     * Validate website content against the record and return the sanitized shape.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handle(array $data, WebsiteContent $content, bool $article): array
    {
        // The wrapper rejects unknown content fields, while the validators below report errors on clean input paths.
        Validator::make(['content' => $data], ['content' => ['required', 'array:title,slug,excerpt,body_json,sections,seo,cover']])->validate();
        $validated = Validator::make($data, $this->rules($data, $content, $article))->validate();
        foreach ($validated['sections'] as $index => $section) {
            $validated['sections'][$index]['visible'] = (bool) $section['visible'];
            if ($section['type'] === 'articles') {
                $validated['sections'][$index]['featured'] = (bool) $section['featured'];
                $validated['sections'][$index]['limit'] = (int) $section['limit'];
            }
            if (isset($section['image'])) {
                $validated['sections'][$index]['image'] = $this->image($section['image']);
            }
            if ($section['type'] === 'introduction') {
                $validated['sections'][$index]['body'] = $this->renderContent->handle($section['body_json'])['html'];
            }
        }
        if ($validated['cover'] !== null) {
            $validated['cover'] = $this->image($validated['cover']);
        }
        if (isset($validated['seo']['image'])) {
            $validated['seo']['image'] = $this->image($validated['seo']['image']);
        }
        $rendered = $this->renderContent->handle($validated['body_json']);

        return [...$validated, 'body_html' => $rendered['html'], 'body_text' => $rendered['text']];
    }

    public function urlRule(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || preg_match('/[\x00-\x20\x7f\\\\]/', $value) || ! preg_match('~^(https?://[^/]+|/(?!/)|mailto:|tel:|\#)~i', $value)) {
                $fail(__('validation.url', ['attribute' => $attribute]));
            }
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, list<mixed>>
     */
    private function rules(array $data, WebsiteContent $content, bool $article): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [$article ? 'required' : 'prohibited', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'excerpt' => ['present', 'nullable', 'string', 'max:2000'],
            'body_json' => ['required', 'array'],
            'sections' => ['present', 'array', 'list', 'max:20', $this->uniqueSectionIds()],
            'seo' => ['present', 'array:title,description,robots,image'],
            'seo.title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seo.description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'seo.robots' => ['sometimes', Rule::in(['index,follow', 'noindex,nofollow'])],
            'cover' => ['present', 'nullable', 'array:media_id,alt'],
        ];
        foreach (array_values($data['sections'] ?? []) as $index => $section) {
            if (! is_array($section)) {
                continue;
            }
            $prefix = 'sections.'.$index;
            $rules[$prefix.'.id'] = ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/'];
            $rules[$prefix.'.type'] = ['required', Rule::in(self::SECTION_TYPES)];
            $rules[$prefix.'.visible'] = ['required', 'boolean'];
            $rules[$prefix.'.title'] = ['required', 'string', 'max:255'];
            $fields = $this->fields($section);
            $keys = ['id', 'type', 'visible', 'title', ...array_keys($fields)];
            $rules[$prefix] = ['array:'.implode(',', array_unique(array_map(static fn (string $key): string => explode('.', $key)[0], $keys)))];
            foreach ($fields as $field => $fieldRules) {
                $rules[$prefix.'.'.$field] = $fieldRules;
            }
            if (isset($section['image'])) {
                $rules = [...$rules, ...$this->imageRules($prefix.'.image', $content)];
            }
            if (isset($section['cta'])) {
                $rules = [...$rules, ...$this->linkRules($prefix.'.cta')];
            }
        }
        if (($data['cover'] ?? null) !== null) {
            $rules = [...$rules, ...$this->imageRules('cover', $content)];
        }
        if (($data['seo']['image'] ?? null) !== null) {
            $rules = [...$rules, ...$this->imageRules('seo.image', $content)];
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, list<mixed>>
     */
    private function fields(array $section): array
    {
        return match ($section['type'] ?? null) {
            'hero' => [
                'description' => ['present', 'nullable', 'string', 'max:2000'],
                'image' => ['present', 'nullable', 'array:media_id,alt'],
                'cta' => ['present', 'nullable', 'array:label,href'],
            ],
            'introduction' => ['body_json' => ['required', 'array']],
            'services', 'strengths' => [
                'items' => ['required', 'array', 'list', 'max:20'],
                'items.*' => ['array:title,description'],
                'items.*.title' => ['required', 'string', 'max:255'],
                'items.*.description' => ['present', 'nullable', 'string', 'max:2000'],
            ],
            'articles' => ['featured' => ['required', 'boolean'], 'limit' => ['required', 'integer', 'min:1', 'max:12']],
            'contact' => [
                'description' => ['present', 'nullable', 'string', 'max:2000'],
                'cta' => ['required', 'array:label,href'],
            ],
            default => [],
        };
    }

    /** @return Closure(string, mixed, Closure): void */
    private function uniqueSectionIds(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            $ids = [];
            foreach (is_array($value) ? $value : [] as $section) {
                $id = is_array($section) ? ($section['id'] ?? null) : null;
                if (is_string($id)) {
                    if (in_array($id, $ids, true)) {
                        $fail(__('validation.distinct', ['attribute' => $attribute.'.*.id']));

                        return;
                    }
                    $ids[] = $id;
                }
            }
        };
    }

    /**
     * @param  array<string, mixed>  $image
     * @return array{media_id: int, alt: mixed}
     */
    private function image(array $image): array
    {
        return ['media_id' => (int) $image['media_id'], 'alt' => $image['alt']];
    }

    /** @return array<string, list<mixed>> */
    private function imageRules(string $prefix, WebsiteContent $content): array
    {
        return [
            $prefix.'.media_id' => [
                'required', 'integer',
                Rule::exists('media', 'id')->where('model_type', (new WebsiteContent)->getMorphClass())->where('model_id', $content->id)->where('collection_name', 'website-images'),
            ],
            $prefix.'.alt' => ['required', 'string', 'max:255'],
        ];
    }

    /** @return array<string, list<mixed>> */
    private function linkRules(string $prefix): array
    {
        return [
            $prefix.'.label' => ['required', 'string', 'max:100'],
            $prefix.'.href' => ['required', 'string', 'max:2000', $this->urlRule()],
        ];
    }
}
