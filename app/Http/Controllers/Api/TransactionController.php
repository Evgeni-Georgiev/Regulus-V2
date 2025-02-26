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

class TransactionController extends Controller
{
    public function index(Request $request): TransactionCollection
    {
        $transactions = Transaction::all();

        return new TransactionCollection($transactions);
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
