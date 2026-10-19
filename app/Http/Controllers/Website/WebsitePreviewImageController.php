<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Actions\Website\GetWebsiteImage;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class WebsitePreviewImageController extends Controller
{
    public function __invoke(Request $request, GetWebsiteImage $getImage): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $media = $getImage->handle((int) $request->route('image'), $user);
        abort_if($media === null, 404);

        $response = $media->toInlineResponse($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
