<?php

use App\Http\Controllers\Api\CoinController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\PortfolioSnapshotController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Authentication Routes (Public)
Route::prefix('auth')->group(function () {
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/password/forgot', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->name('verification.verify');
});

// Protected Authentication Routes
Route::middleware(['auth:sanctum'])->prefix('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/user', [LoginController::class, 'user']);
    Route::post('/password/change', [PasswordResetController::class, 'changePassword']);
    Route::post('/email/resend', [EmailVerificationController::class, 'resend']);
    Route::get('/email/status', [EmailVerificationController::class, 'status']);
});

// Existing protected routes
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Debug route to test authorization
//Route::middleware(['auth:sanctum'])->get('/debug/auth', function (Request $request) {
//    $user = $request->user();
//    $portfolios = Portfolio::where('user_id', $user->id)->get();
//
//    return response()->json([
//        'authenticated_user' => [
//            'id' => $user->id,
//            'name' => $user->name,
//            'email' => $user->email
//        ],
//        'user_portfolios_count' => $portfolios->count(),
//        'user_portfolios' => $portfolios->map(function ($portfolio) {
//            return [
//                'id' => $portfolio->id,
//                'name' => $portfolio->name,
//                'user_id' => $portfolio->user_id
//            ];
//        }),
//        'all_portfolios_for_debug' => Portfolio::all(['id', 'name', 'user_id']),
//    ]);
//});

// Protected API routes - require authentication
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/portfolios/{portfolio}/history', [PortfolioController::class, 'history']);
    Route::get('/portfolios/{portfolio}/snapshot', [PortfolioSnapshotController::class, 'show']);

    Route::apiResource('portfolios', PortfolioController::class);
    Route::apiResource('transactions', TransactionController::class);
});

// Coins can be public (or protect if needed)
Route::apiResource('coins', CoinController::class);
