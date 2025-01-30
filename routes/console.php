<?php

use App\Console\Commands\CoinFetch;
use App\Jobs\SyncCoinDataJob;
use App\Jobs\UpdateCoinPriceJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command(CoinFetch::class)->everyFifteenSeconds();
Schedule::job(new UpdateCoinPriceJob())->everyFifteenSeconds();
Schedule::job(new SyncCoinDataJob())->everyOddHour();
