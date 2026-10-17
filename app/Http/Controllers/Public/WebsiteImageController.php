<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Website\GetWebsiteImage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class WebsiteImageController extends Controller
{
    public function __invoke(int $image, Request $request, GetWebsiteImage $getImage): StreamedResponse
    {
        $media = $getImage->handle($image);
        abort_if($media === null, 404);

        $response = $media->toInlineResponse($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
