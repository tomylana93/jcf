<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Website\BuildPublicAboutPage;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class AboutController extends Controller
{
    public function __invoke(BuildPublicAboutPage $page): Response
    {
        $website = $page->handle();
        abort_if($website === null, 404);

        return Inertia::render('public/Articles', ['website' => $website]);
    }
}
