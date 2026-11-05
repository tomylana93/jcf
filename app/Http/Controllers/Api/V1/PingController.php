<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class PingController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['status' => 'ok', 'version' => 'v1']);
    }
}
