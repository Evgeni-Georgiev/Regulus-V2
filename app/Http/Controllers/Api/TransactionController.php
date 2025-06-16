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
    public function __construct()
    {
        // Middleware is already applied at the route level
    }

    public function index(Request $request): TransactionCollection
    {
        try {
            // Use policy authorization
            $this->authorize('viewAny', Transaction::class);
            
            $user = $request->user();
            
            // Start with transactions that belong to portfolios owned by the authenticated user
            $query = Transaction::whereHas('portfolio', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
            
            // Filter by portfolio_id if provided (but still ensure it belongs to the user)
            if ($request->has('portfolio_id')) {
                $portfolioId = $request->input('portfolio_id');
                
                // Verify the portfolio belongs to the authenticated user
                $portfolio = Portfolio::where('id', $portfolioId)
                    ->where('user_id', $user->id)
                    ->first();
                
                if (!$portfolio) {
                    abort(403, 'Access denied. This portfolio does not belong to you.');
                }
                
                $query->where('portfolio_id', $portfolioId);
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
                // Get the coin data for this portfolio (already verified above)
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
                'user_id' => $request->user()->id ?? null,
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
            // Use policy authorization
            $this->authorize('create', Transaction::class);
            
            return DB::transaction(function () use ($request) {
                $user = $request->user();
                $portfolioId = $request->input('portfolio_id');
                
                // Verify the portfolio belongs to the authenticated user
                $portfolio = Portfolio::where('id', $portfolioId)
                    ->where('user_id', $user->id)
                    ->first();
                
                if (!$portfolio) {
                    abort(403, 'Access denied. This portfolio does not belong to you.');
                }

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
        // Use policy authorization - this will check if transaction belongs to user's portfolio
        $this->authorize('view', $transaction);

        return new TransactionResource($transaction);
    }

    public function update(TransactionUpdateRequest $request, Transaction $transaction): TransactionResource
    {
        // Use policy authorization - this will check if transaction belongs to user's portfolio
        $this->authorize('update', $transaction);

        $transaction->update($request->validated());

        return new TransactionResource($transaction);
    }

    public function destroy(Request $request, Transaction $transaction): Response
    {
        // Use policy authorization - this will check if transaction belongs to user's portfolio
        $this->authorize('delete', $transaction);

        $transaction->delete();

        return response()->noContent();
    }
}
