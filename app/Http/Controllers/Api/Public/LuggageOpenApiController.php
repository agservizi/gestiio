<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\OpenApi\LuggageDepositOpenApiSpec;
use Illuminate\Http\JsonResponse;

class LuggageOpenApiController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(LuggageDepositOpenApiSpec::publicSpec());
    }

    public function admin(): JsonResponse
    {
        return response()->json(LuggageDepositOpenApiSpec::adminSpec());
    }
}
