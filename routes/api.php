<?php

use App\Http\Controllers\Api\CoinController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\PortfolioSnapshotController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\ExchangeApiConnectionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/portfolios/{portfolio}/history', [PortfolioController::class, 'history']);
Route::get('/portfolios/{portfolio}/snapshot', [PortfolioSnapshotController::class, 'show']);


Route::apiResource('coins', CoinController::class);

Route::apiResource('portfolios', PortfolioController::class);

Route::apiResource('transactions', TransactionController::class);

// Exchange connections
//Route::middleware('auth:sanctum')->group(function () {
    Route::get('/exchange-connections', [ExchangeApiConnectionController::class, 'index']);
    Route::post('/exchange-connections', [ExchangeApiConnectionController::class, 'store']);
    Route::put('/exchange-connections/{id}', [ExchangeApiConnectionController::class, 'update']);
    Route::delete('/exchange-connections/{id}', [ExchangeApiConnectionController::class, 'destroy']);
    Route::post('/exchange-connections/{id}/sync', [ExchangeApiConnectionController::class, 'sync']);
//});
