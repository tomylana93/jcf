<?php

declare(strict_types=1);

namespace App\Http\Controllers\Search;

use App\Actions\Search\SearchGlobal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\GlobalSearchRequest;
use Illuminate\Http\JsonResponse;

final class GlobalSearchController extends Controller
{
    public function __invoke(GlobalSearchRequest $request, SearchGlobal $search): JsonResponse
    {
        return response()->json($search->handle($request->user(), $request->term()));
    }
}
