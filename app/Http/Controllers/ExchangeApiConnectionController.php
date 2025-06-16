<?php

namespace App\Http\Controllers;

use App\Models\ExchangeApiConnection;
use App\Services\ExchangeApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExchangeApiConnectionController extends Controller
{
    protected $exchangeApiService;
    
    public function __construct(ExchangeApiService $exchangeApiService)
    {
        $this->exchangeApiService = $exchangeApiService;
    }

    /**
     * Display a listing of the user's exchange connections
     */
    public function index(Request $request)
    {
        $connections = $request->user()->exchangeApiConnections()->get();
        
        return response()->json([
            'connections' => $connections->map(function ($connection) {
                // Mask sensitive data
                return [
                    'id' => $connection->id,
                    'exchange_name' => $connection->exchange_name,
                    'api_key' => '••••' . substr($connection->api_key, -4),
                    'last_synced_at' => $connection->last_synced_at,
                    'is_active' => $connection->is_active,
                    'created_at' => $connection->created_at
                ];
            })
        ]);
    }

    /**
     * Store a new exchange connection
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exchange_name' => 'required|string|in:binance,bybit,gate_io',
            'api_key' => 'required|string',
            'api_secret' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if connection already exists
        $existingConnection = $request->user()->exchangeApiConnections()
            ->where('exchange_name', $request->exchange_name)
            ->first();
            
        if ($existingConnection) {
            return response()->json([
                'message' => 'Connection to this exchange already exists'
            ], 422);
        }

        // Create new connection
        $connection = new ExchangeApiConnection([
            'user_id' => $request->user()->id,
            'exchange_name' => $request->exchange_name,
            'api_key' => $request->api_key,
            'api_secret' => $request->api_secret,
        ]);
        
        $connection->save();
        
        // Test the connection
        try {
            $this->exchangeApiService->syncExchangeTransactions($connection);
            return response()->json([
                'message' => 'Exchange connected successfully',
                'connection_id' => $connection->id
            ]);
        } catch (\Exception $e) {
            $connection->delete();
            return response()->json([
                'message' => 'Failed to connect to exchange: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an exchange connection
     */
    public function update(Request $request, $id)
    {
        $connection = $request->user()->exchangeApiConnections()->findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'api_key' => 'sometimes|required|string',
            'api_secret' => 'sometimes|required|string',
            'is_active' => 'sometimes|required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('api_key')) {
            $connection->api_key = $request->api_key;
        }
        
        if ($request->has('api_secret')) {
            $connection->api_secret = $request->api_secret;
        }
        
        if ($request->has('is_active')) {
            $connection->is_active = $request->is_active;
        }
        
        $connection->save();
        
        return response()->json([
            'message' => 'Connection updated successfully'
        ]);
    }

    /**
     * Remove an exchange connection
     */
    public function destroy(Request $request, $id)
    {
        $connection = $request->user()->exchangeApiConnections()->findOrFail($id);
        $connection->delete();
        
        return response()->json([
            'message' => 'Connection removed successfully'
        ]);
    }

    /**
     * Manually sync transactions from an exchange
     */
    public function sync(Request $request, $id)
    {
        $connection = $request->user()->exchangeApiConnections()->findOrFail($id);
        
        try {
            $result = $this->exchangeApiService->syncExchangeTransactions($connection);
            
            if ($result) {
                return response()->json([
                    'message' => 'Transactions synced successfully'
                ]);
            } else {
                return response()->json([
                    'message' => 'No new transactions found'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to sync transactions: ' . $e->getMessage()
            ], 500);
        }
    }
} 