<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Models\User;
use App\Models\WebsiteContent;
use App\Settings\WebsiteSettings;
use Illuminate\Support\Arr;

final readonly class BuildWebsiteArticlePreview
{
    public function __construct(private ValidateWebsiteContent $validate, private WebsiteSettings $settings, private BuildArticlePageContent $page, private BuildWebsiteNavigation $navigation) {}

    /** @return array<string, mixed> */
    public function handle(WebsiteContent $article): array
    {
        $this->settings->refresh();
        $content = $this->validate->handle(Arr::except($article->draft, ['body_html', 'body_text']), $article, true);
        $content['author'] = User::query()->whereKey($article->author_id)->value('name') ?? '';

        return [
            'preset' => $this->settings->preset,
            'preferences' => $this->settings->preferences,
            'navigation' => $this->navigation->handle($this->settings->navigation, route('articles.index', absolute: false)),
            'footer' => $this->settings->footer,
            'preview' => ['mode' => 'draft'],
            'page' => $this->page->handle($content, 'article', true),
        ];
    }
}
