<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiClientResource;
use Illuminate\Http\Request;

final class CurrentApiClientController extends Controller
{
    public function __invoke(Request $request): ApiClientResource
    {
        return new ApiClientResource($request->user());
    }
}
