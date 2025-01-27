<?php

use Illuminate\Support\Facades\Route;

//Route::get('/', function () {
//    return ['Laravel' => app()->version()];
//});

// Single route to serve the SPA
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');

//Route::get('coins', [\App\Http\Controllers\CoinController::class, 'index'])->name('portfolios.index');

require __DIR__.'/auth.php';
