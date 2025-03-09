<?php

namespace App\Services\Exchanges;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class BinanceClient
{
    protected $apiKey;
    protected $apiSecret;
    protected $baseUrl = 'https://api.binance.com';
    
    public function __construct($apiKey, $apiSecret)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }
    
    /**
     * Get transactions since a specific date
     */
    public function getTransactions(Carbon $since)
    {
        $timestamp = time() * 1000;
        $sinceTimestamp = $since->timestamp * 1000;
        
        // Get account information
        $response = $this->signedRequest('GET', '/api/v3/account', [
            'timestamp' => $timestamp
        ]);
        
        if (!isset($response['balances'])) {
            return [];
        }
        
        $transactions = [];
        
        // For each asset with balance > 0, get trade history
        foreach ($response['balances'] as $balance) {
            if (floatval($balance['free']) > 0 || floatval($balance['locked']) > 0) {
                $trades = $this->getTradeHistory($balance['asset'], $sinceTimestamp);
                $transactions = array_merge($transactions, $trades);
            }
        }
        
        return $transactions;
    }
    
    /**
     * Get trade history for a specific symbol
     */
    protected function getTradeHistory($asset, $sinceTimestamp)
    {
        $symbols = $this->getPossibleTradingPairs($asset);
        $allTrades = [];
        
        foreach ($symbols as $symbol) {
            $timestamp = time() * 1000;
            
            $response = $this->signedRequest('GET', '/api/v3/myTrades', [
                'symbol' => $symbol,
                'startTime' => $sinceTimestamp,
                'timestamp' => $timestamp
            ]);
            
            if (!is_array($response)) {
                continue;
            }
            
            foreach ($response as $trade) {
                $allTrades[] = [
                    'id' => $trade['id'],
                    'symbol' => $symbol,
                    'price' => $trade['price'],
                    'amount' => $trade['qty'],
                    'fee' => $trade['commission'],
                    'feeCurrency' => $trade['commissionAsset'],
                    'type' => $trade['isBuyer'] ? 'BUY' : 'SELL',
                    'timestamp' => $trade['time']
                ];
            }
        }
        
        return $allTrades;
    }
    
    /**
     * Get possible trading pairs for an asset
     */
    protected function getPossibleTradingPairs($asset)
    {
        $response = Http::get("{$this->baseUrl}/api/v3/exchangeInfo");
        $pairs = [];
        
        if (!isset($response['symbols'])) {
            return $pairs;
        }
        
        foreach ($response['symbols'] as $symbol) {
            if ($symbol['baseAsset'] === $asset || $symbol['quoteAsset'] === $asset) {
                $pairs[] = $symbol['symbol'];
            }
        }
        
        return $pairs;
    }
    
    /**
     * Make a signed API request
     */
    protected function signedRequest($method, $endpoint, $params = [])
    {
        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->apiSecret);
        $url = "{$this->baseUrl}{$endpoint}?{$queryString}&signature={$signature}";
        
        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->$method($url);
        
        return $response->json();
    }
} 