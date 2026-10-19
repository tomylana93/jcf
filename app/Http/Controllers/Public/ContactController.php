<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Website\BuildPublicContactPage;
use App\Actions\Website\StoreContactMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Website\StoreContactMessageRequest;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ContactController extends Controller
{
    public function index(BuildPublicContactPage $page): Response
    {
        $website = $page->handle();
        abort_if($website === null, 404);

        return Inertia::render('public/Contact', ['website' => $website]);
    }

    public function store(StoreContactMessageRequest $request, StoreContactMessage $store): JsonResponse
    {
        $store->handle($request->validated());

        return response()->json(['message' => __('contact.status.sent')], 201)->header('Cache-Control', 'no-store, private');
    }
}
