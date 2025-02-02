<?php

namespace App\Services;

use App\Enums\CMCResponseStatusEnum;
use App\Models\ApiFetchLog;
use App\Models\Coin;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

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
        'API_RESPONSE' => 'cmc.api.response',
        'COINS_DATA_STATIC' => 'cmc.coins.data.static',
        'COINS_DATA' => 'cmc.coins.data',
    ];

    private const CACHE_DURATIONS = [
        'API_RESPONSE' => 180, // 3 minutes
        'COINS_DATA_DYNAMIC' => 300, // 5 minutes
        'COINS_DATA_STATIC' => 14400, // 4 hours
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
     * Retrieves coin data, preferring cached data but falling back to the database if
     * the API call fails. Returns the data as a collection of coins.
     *
     * @return Collection The collection of coin data, keyed by symbol.
     */
    public function getCoinData(): Collection
    {
        try {
            $staticData = $this->cacheApiData(
                self::CACHE_KEYS['COINS_DATA_STATIC'],
                now()->addSeconds(14400), // 4 hours
                fn() => $this->fetchStaticDataFromApi()
            );
            $dynamicData = $this->cacheApiData(
                self::CACHE_KEYS['COINS_DATA'],
                15,
                fn() => $this->fetchDynamicDataFromApi()
            );

            // If API fetch was successful and data is valid, log it and return data
            if ($this->isValidData($dynamicData)) {
                ApiFetchLog::create([
                    'type' => 'API',
                    'source' => config('services.cmc.api_url'),
                    'success' => true,
                    'error_message' => null
                ]);

                return $staticData->map(function ($coin, $symbol) use ($dynamicData) {
                    return array_merge($coin, [
                        'price' => $dynamicData[$symbol]['price'] ?? 0,
                        'market_cap' => $dynamicData[$symbol]['market_cap'] ?? 0,
                        'percent_change_1h' => $dynamicData[$symbol]['percent_change_1h'] ?? 0,
                        'percent_change_24h' => $dynamicData[$symbol]['percent_change_24h'] ?? 0,
                        'percent_change_7d' => $dynamicData[$symbol]['percent_change_7d'] ?? 0,
                        'volume_24h' => $dynamicData[$symbol]['volume_24h'] ?? 0,
                    ]);
                });
            }

            // If fresh API data is invalid, log failure and fallback to database
            Log::warning("API returned invalid or zero dynamic data. Falling back to database.");

            ApiFetchLog::create([
                'type' => 'API',
                'source' => config('services.cmc.api_url'),
                'success' => false,
                'error_message' => "API returned zero values."
            ]);

            return $this->getCoinsFromDatabase();
        } catch (Exception $e) {
            Log::error('Error fetching coins: ' . $e->getMessage());

            ApiFetchLog::create([
                'type' => 'API',
                'source' => config('services.cmc.api_url'),
                'success' => false,
                'error_message' => $e->getMessage()
            ]);

            return $this->getCoinsFromDatabase();
        }
    }

    /**
     * Fetches fresh static data from the CoinMarketCap API.
     * Falls back to the database if the API call fails.
     *
     * @return Collection The collection of static coin data.
     */
    private function fetchStaticDataFromApi(): Collection
    {
        try {
            $response = $this->sendAPIRequest();

            if (!$response->successful() || empty($response->json('data'))) {
                throw new Exception("Invalid API response: Empty static data.");
            }

            return collect($response->json('data'))->mapWithKeys(fn($coin) => [
                $coin['symbol'] => [
                    'name' => $coin['name'],
                    'symbol' => $coin['symbol'],
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Failed to fetch static data from API: ' . $e->getMessage());
            return $this->getStaticDataFromDatabase();
        }
    }

    /**
     * Caches API data if new data is fetched, or returns cached data.
     *
     * @param string $cacheKey The cache key.
     * @param \DateTime|int $cacheDuration The duration to keep the data in the cache.
     * @param callable $fetchDataFromApi A callable to fetch fresh data.
     * @return Collection The cached or fresh data as a collection.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function cacheApiData(string $cacheKey, \DateTime|int $cacheDuration, callable $fetchDataFromApi): Collection
    {
        $cachedData = cache()->get($cacheKey);
        $newData = $fetchDataFromApi();

        if ($newData->isNotEmpty()) {
            cache()->put($cacheKey, $newData, $cacheDuration);
            return $newData;
        }

        return $cachedData ?? collect();
    }

    /**
     * Retrieves volatile coins data from the CoinMarketCap Api.
     * If fetch fails, Fetches coins data from database.
     *
     * @return Collection
     */
    private function fetchDynamicDataFromApi(): Collection
    {
        try {
            $response = $this->sendAPIRequest();

            if (!$response->successful() || empty($response->json('data'))) {
                throw new Exception("Invalid API response: Empty dynamic data.");
            }

            $data = collect($response->json('data'))->mapWithKeys(fn($coin) => [
                $coin['symbol'] => [
                    'price' => $coin['quote']['USD']['price'] ?? 0,
                    'market_cap' => $coin['quote']['USD']['market_cap'] ?? 0,
                    'percent_change_1h' => $coin['quote']['USD']['percent_change_1h'] ?? 0,
                    'percent_change_24h' => $coin['quote']['USD']['percent_change_24h'] ?? 0,
                    'percent_change_7d' => $coin['quote']['USD']['percent_change_7d'] ?? 0,
                    'volume_24h' => $coin['quote']['USD']['volume_24h'] ?? 0,
                ],
            ]);

            if (!$this->isValidData($data)) {
                throw new Exception("API returned only zero values.");
            }

            return $data;

        } catch (Exception $e) {
            Log::error('Failed to fetch fresh data from API: ' . $e->getMessage());
            return $this->getDynamicDataFromDatabase();
        }
    }

    private function isValidData(Collection $coins): bool
    {
        return $coins->isNotEmpty() && $coins->filter(fn($coin) => $coin['price'] > 0)->isNotEmpty();
    }

    /**
     * Retrieve static data (name, symbol) from the database.
     *
     * @return Collection The collection of static coin data.
     */
    private function getStaticDataFromDatabase(): Collection
    {
        return Coin::all()->mapWithKeys(fn($coin) => [
            $coin->symbol => [
                'name' => $coin->name,
                'symbol' => $coin->symbol,
            ],
        ]);
    }

    /**
     * Retrieve dynamic data (price, market_cap, volume_24h etc.) from the database.
     *
     * @return Collection The collection of dynamic coin data.
     */
    private function getDynamicDataFromDatabase(): Collection
    {
        return Coin::all()->mapWithKeys(fn($coin) => [
            $coin->symbol => [
                'price' => $coin->price,
                'market_cap' => $coin->market_cap,
                'percent_change_1h' => $coin->percent_change_1h,
                'percent_change_24h' => $coin->percent_change_24h,
                'percent_change_7d' => $coin->percent_change_7d,
                'volume_24h' => $coin->volume_24h,
            ],
        ]);
    }

    /**
     * Retrieve coin data from the database, structuring it as a collection.
     *
     * @return Collection The collection of coins from the database, keyed by symbol.
     */
    private function getCoinsFromDatabase(): Collection
    {
        ApiFetchLog::create([
            'type' => 'Database',
            'source' => 'Database',
            'success' => true,
            'error_message' => null
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
