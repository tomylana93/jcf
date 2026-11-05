<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Documentation\SearchDocuments;
use App\Support\SearchProvider;
use Illuminate\Support\ServiceProvider;

final class DocumentationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag(SearchDocuments::class, SearchProvider::class);
    }
}
