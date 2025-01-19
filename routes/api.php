<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


Route::apiResource('coins', App\Http\Controllers\CoinController::class);

Route::apiResource('portfolios', App\Http\Controllers\PortfolioController::class);

Route::apiResource('transactions', App\Http\Controllers\TransactionController::class);
