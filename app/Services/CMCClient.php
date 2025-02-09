<?php

namespace App\Services;

use App\Enums\CMCResponseStatusEnum;
use App\Events\CoinPriceUpdated;
use App\Models\ApiFetchLog;
use App\Models\Coin;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class CMCClient
 *
 * Handles interactions with the CoinMarketCap API to fetch cryptocurrency data,
 * manages caching of static and dynamic data, and provides fallback mechanisms using the database,
 * if no data is fetched from api.
 */
class CMCClient
{
    /**
     * Indicates if the last response was successful.
     *
     * @var CMCResponseStatusEnum
     */
    protected CMCResponseStatusEnum $responseState = CMCResponseStatusEnum::SUCCESS;

    private const CACHE_KEYS = [
        'API_FRESH_DATA' => 'cmc.api.fresh.data',
        'API_CACHED_DATA' => 'cmc.api.cached.data',
    ];

    private const CACHE_DURATIONS = [
        'FRESH_DATA' => 15, // 15 seconds for fresh API data to be cached
        'CACHED_DATA' => 300, // 5 minutes for cached API data to be cached
    ];

    /**
     * Gets the status of the last response.
     *
     * @return CMCResponseStatusEnum
     */
    public function getCMCResponseStatus(): CMCResponseStatusEnum
    {
        return $this->responseState;
    }

    /**
     * Sends a request to the CoinMarketCap API to fetch cryptocurrency data.
     *
     * @return Response The HTTP response from the CoinMarketCap API.
     * @throws ConnectionException
     */
    public function sendAPIRequest(): Response
    {
        return Http::withHeaders([
            'X-CMC_PRO_API_KEY' => config('services.cmc.api_key'),
            'Accept' => 'application/json',
        ])->get(config('services.cmc.api_url'), [
            'start' => 1,
            'limit' => 100,
            'convert' => 'USD',
        ]);
    }

    /**
     * Retrieves coin data following the priority: Fresh API → Cached API → Database.
     *
     * @return Collection
     */
    public function getCoinData(): Collection
    {
        try {
            // Try to get/fetch fresh API data
            $freshData = $this->getFreshApiData();
            if ($this->isValidData($freshData)) {
                event(new CoinPriceUpdated($freshData->toArray()));
                return $freshData;
            }

            // Try to get cached API data
            $cachedData = $this->getCachedApiData();
            if ($this->isValidData($cachedData)) {
                Log::info('Using cached API data');
                return $cachedData;
            }

            // Fallback to database data if API data fetch fails
            Log::info('Using database data as fallback');
            return $this->getCoinsFromDatabase();

        } catch (Exception $e) {
            Log::error('Error in getCoinData: ' . $e->getMessage());
            return $this->getCoinsFromDatabase();
        }
    }

    /**
     * Returns the current data source being used.
     *
     * @return string
     */
    public function getDataSource(): string
    {
        if (cache()->has(self::CACHE_KEYS['API_FRESH_DATA'])) {
            return 'Fresh API';
        }

        if (cache()->has(self::CACHE_KEYS['API_CACHED_DATA'])) {
            return 'Cached API';
        }

        return 'Database';
    }

    /**
     * Attempts to fetch fresh data from API and cache it.
     *
     * @return Collection
     */
    private function getFreshApiData(): Collection
    {
        try {
            $response = $this->sendAPIRequest();

            if (!$response->successful() || empty($response->json('data'))) {
                throw new Exception("Invalid API response");
            }

            $freshData = $this->processApiResponse($response->json('data'));

            if ($this->isValidData($freshData)) {
                // Cache as fresh data (short duration)
                cache()->put(
                    self::CACHE_KEYS['API_FRESH_DATA'],
                    $freshData,
                    self::CACHE_DURATIONS['FRESH_DATA']
                );

                // Cache as backup data (longer duration)
                cache()->put(
                    self::CACHE_KEYS['API_CACHED_DATA'],
                    $freshData,
                    self::CACHE_DURATIONS['CACHED_DATA']
                );

                ApiFetchLog::create([
                    'type' => 'API',
                    'source' => config('services.cmc.api_url'),
                    'success' => true,
                    'error_message' => null
                ]);

                return $freshData;
            }

            throw new Exception("Invalid data format from API");

        } catch (Exception $e) {
            Log::error('Failed to fetch fresh API data: ' . $e->getMessage());

            // Return empty collection to trigger fallback
            return collect();
        }
    }

    /**
     * Retrieves cached API data.
     *
     * @return Collection
     */
    private function getCachedApiData(): Collection
    {
        // Try fresh data cache
        $freshData = cache()->get(self::CACHE_KEYS['API_FRESH_DATA']);
        if ($this->isValidData($freshData)) {
            return $freshData;
        }

        // Try backup cache
        $cachedData = cache()->get(self::CACHE_KEYS['API_CACHED_DATA']);
        if ($this->isValidData($cachedData)) {
            return $cachedData;
        }

        return collect();
    }

    /**
     * Processes API response into standardized format.
     *
     * @param array $apiData
     * @return Collection
     */
    private function processApiResponse(array $apiData): Collection
    {
        return collect($apiData)->mapWithKeys(function ($coin) {
            return [
                $coin['symbol'] => [
                    'name' => $coin['name'],
                    'symbol' => $coin['symbol'],
                    'price' => $coin['quote']['USD']['price'] ?? 0,
                    'market_cap' => $coin['quote']['USD']['market_cap'] ?? 0,
                    'percent_change_1h' => $coin['quote']['USD']['percent_change_1h'] ?? 0,
                    'percent_change_24h' => $coin['quote']['USD']['percent_change_24h'] ?? 0,
                    'percent_change_7d' => $coin['quote']['USD']['percent_change_7d'] ?? 0,
                    'volume_24h' => $coin['quote']['USD']['volume_24h'] ?? 0,
                ]
            ];
        });
    }

    /**
     * Validates that the data collection is not empty and contains valid prices.
     *
     * @param Collection|null $data
     * @return bool
     */
    private function isValidData(?Collection $data): bool
    {
        if (!$data) return false;

        return $data->isNotEmpty() && $data->filter(fn($coin) => ($coin['price'] ?? 0) > 0)->isNotEmpty();
    }

    /**
     * Retrieves coin data from the database as last resort.
     *
     * @return Collection
     */
    private function getCoinsFromDatabase(): Collection
    {
        ApiFetchLog::create([
            'type' => 'Database',
            'source' => 'Database',
            'success' => false,
            'error_message' => 'Using database as fallback after API and cache failure'
        ]);

        return Coin::all()->mapWithKeys(function ($coin) {
            return [
                $coin->symbol => [
                    'name' => $coin->name,
                    'symbol' => $coin->symbol,
                    'price' => $coin->price,
                    'market_cap' => $coin->market_cap,
                    'percent_change_1h' => $coin->percent_change_1h,
                    'percent_change_24h' => $coin->percent_change_24h,
                    'percent_change_7d' => $coin->percent_change_7d,
                    'volume_24h' => $coin->volume_24h,
                ],
            ];
        });
    }
}
