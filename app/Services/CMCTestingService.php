<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CMCTestingService
{
    /**
     * @throws ConnectionException
     */
    public function testApiFetch($params = []): array
    {
        $response = Http::withHeaders([
        'X-CMC_PRO_API_KEY' => config('services.cmc.api_key'),
        'Accept' => 'application/json',
    ])->get(config('services.cmc.api_url'), $params);

        if($response->successful()) {
            return $response->json();
        }

        Log::warning('CMC API responded with an error.', ['response' => $response->json()]);
        return [];
    }
}
