<?php

declare(strict_types=1);

namespace App\Http\Controllers\Search;

use App\Actions\Search\SearchGlobal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\GlobalSearchRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

final class GlobalSearchController extends Controller
{
    public function __invoke(GlobalSearchRequest $request, SearchGlobal $search): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return response()->json($search->handle($user, $request->string('query')->toString()))->header('Cache-Control', 'no-store');
    }
}
