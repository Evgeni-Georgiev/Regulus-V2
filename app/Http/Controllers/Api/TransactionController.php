<?php

namespace App\Http\Controllers\Api;

use App\Enums\TransactionTypeEnum;
use App\Exceptions\InsufficientBalanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionStoreRequest;
use App\Http\Requests\TransactionUpdateRequest;
use App\Http\Resources\TransactionCollection;
use App\Http\Resources\TransactionResource;
use App\Models\Coin;
use App\Models\Portfolio;
use App\Models\PortfolioHistory;
use App\Models\Transaction;
use App\Services\CMCClient;
use App\Services\PortfolioService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function index(Request $request): TransactionCollection
    {
        try {
            $query = Transaction::query();
            
            // Filter by portfolio_id if provided
            if ($request->has('portfolio_id')) {
                $query->where('portfolio_id', $request->input('portfolio_id'));
            }
            
            // Filter by coin_id if provided
            if ($request->has('coin_id')) {
                $query->where('coin_id', $request->input('coin_id'));
            }
            
            $transactions = $query->get();
            
            // Calculate transaction sums safely
            $totalBought = 0;
            $totalSold = 0;
            
            foreach ($transactions as $transaction) {
                $amount = $transaction->quantity * $transaction->buy_price;
                
                if ($transaction->transaction_type === 'buy') {
                    $totalBought += $amount;
                } elseif ($transaction->transaction_type === 'sell') {
                    $totalSold += $amount;
                }
            }
            
            $netPosition = $totalBought - $totalSold;
            
            // Calculate profit/loss if we have portfolio_id and coin_id
            $profitLoss = 0;
            $currentValue = 0;
            $averageBuyPrice = 0;
            $totalHoldingQuantity = 0;
            
            if ($request->has('portfolio_id') && $request->has('coin_id')) {
                // Get the coin data for this portfolio
                $portfolio = Portfolio::find($request->input('portfolio_id'));
                if ($portfolio) {
                    $coin = $portfolio->coins()
                        ->where('coin_id', $request->input('coin_id'))
                        ->first();
                    
                    if ($coin) {
                        $averageBuyPrice = $coin->pivot->average_buy_price ?? 0;
                        $totalHoldingQuantity = $coin->pivot->total_holding_quantity ?? 0;
                        $currentPrice = $coin->price ?? 0;
                        
                        // Calculate profit/loss using the same logic from frontend
                        $invested = $averageBuyPrice * $totalHoldingQuantity;
                        $currentValue = $currentPrice * $totalHoldingQuantity;
                        $profitLoss = $currentValue - $invested;
                    }
                }
            }
            
            // Pass these totals to the TransactionCollection
            return (new TransactionCollection($transactions))
                ->additional([
                    'meta' => [
                        'total_bought' => $totalBought,
                        'total_sold' => $totalSold,
                        'net_position' => $netPosition,
                        'profit_loss' => $profitLoss,
                        'current_value' => $currentValue,
                        'average_buy_price' => $averageBuyPrice,
                        'total_holding_quantity' => $totalHoldingQuantity
                    ]
                ]);
                
        } catch (\Exception $e) {
            Log::error('Error in TransactionController@index: ' . $e->getMessage(), [
                'portfolio_id' => $request->input('portfolio_id'),
                'coin_id' => $request->input('coin_id'),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return empty collection with error info
            return (new TransactionCollection([]))
                ->additional([
                    'error' => 'Failed to load transactions',
                    'meta' => [
                        'total_bought' => 0,
                        'total_sold' => 0,
                        'net_position' => 0,
                        'profit_loss' => 0,
                        'current_value' => 0,
                        'average_buy_price' => 0,
                        'total_holding_quantity' => 0
                    ]
                ]);
        }
    }

    /**
     * @throws InsufficientBalanceException
     */
    public function store(TransactionStoreRequest $request): TransactionResource
    {
        try {
            return DB::transaction(function () use ($request) {
                $portfolio = Portfolio::where('id', $request->input('portfolio_id'))->first();

                $portfolioService = app(PortfolioService::class)
                    ->loadPortfolio($portfolio)
                    ->loadCoin(Coin::find($request->input('coin_id')))
                    ->setData($request->validated());

                $previousValue = $portfolioService->getPortfolioDetails(app(CMCClient::class)->getCoinData())['total_value'];

                if ($request->input('transaction_type') === TransactionTypeEnum::SELL) {
                    $totalHolding = app(PortfolioService::class)->getTotalHoldingForCoin(
                        Coin::find($request->input('coin_id'))
                    );

                    if ($totalHolding < $request->input('quantity')) {
                        throw new InsufficientBalanceException("Not enough balance to sell.");
                    }
                }

                $portfolioHistory = PortfolioHistory::create([
                    'portfolio_id' => $portfolio->id,
                    'previous_value' => $previousValue, // setting the value before it was changed due to the creation of the Transaction.
                    'changed_at' => now(),
                ]);

                $transaction = Transaction::create($request->validated());

                $portfolioHistory->update([
                    'change_type' => $transaction->transaction_type,
                    'change_value' => $transaction->quantity * $transaction->buy_price,
                    'new_value' => $portfolioService->getPortfolioDetails(app(CMCClient::class)->getCoinData())['total_value']
                ]);

                return new TransactionResource($transaction);
            });
        } catch (InsufficientBalanceException $e) {
            throw new InsufficientBalanceException($e->getMessage());
        } catch (\Throwable $e) {
            throw new HttpResponseException(response()->json([
                'error' => 'Transaction failed',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }

    public function show(Request $request, Transaction $transaction): TransactionResource
    {
        return new TransactionResource($transaction);
    }

    public function update(TransactionUpdateRequest $request, Transaction $transaction): TransactionResource
    {
        $transaction->update($request->validated());

        return new TransactionResource($transaction);
    }

    public function destroy(Request $request, Transaction $transaction): Response
    {
        $transaction->delete();

        return response()->noContent();
    }
}
