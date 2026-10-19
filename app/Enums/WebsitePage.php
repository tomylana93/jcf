<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;
use App\Concerns\HasOptions;

enum WebsitePage: string
{
    use HasLabel, HasOptions;

    case Home = 'home';
    case About = 'about';
    case Contact = 'contact';
    case ArticleIndex = 'article-index';
}
