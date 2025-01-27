<?php

use App\Http\Controllers\Api\CoinController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


Route::apiResource('coins', CoinController::class);

Route::apiResource('portfolios', PortfolioController::class);

Route::apiResource('transactions', TransactionController::class);
