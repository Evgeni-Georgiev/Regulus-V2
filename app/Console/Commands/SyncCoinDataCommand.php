<?php

namespace App\Console\Commands;

use App\Jobs\SyncCoinDataJob;
use Illuminate\Console\Command;

class SyncCoinDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coins:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync coin data from API to database';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        app(SyncCoinDataJob::class)->dispatch();
        $this->info('Coin data synced successfully.');
    }
}
