<?php

namespace App\Http\Controllers;

use App\Services\CMCTestingService;
use Illuminate\Http\JsonResponse;

class ApiTestController extends Controller
{
    public function test(): JsonResponse
    {
        $apiResponse = app(CMCTestingService::class)->testApiFetch([
            'start' => 1,
            'limit' => 5, // Limit response to 5 coins for testing
            'convert' => 'USD',
        ]);

        return response()->json([
            'status' => $apiResponse ? 'success' : 'error',
            'data' => $apiResponse,
        ]);
    }
}
