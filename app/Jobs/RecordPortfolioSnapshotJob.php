<?php

namespace App\Jobs;

use App\Models\Portfolio;
use App\Models\PortfolioSnapshot;
use App\Services\CMCClient;
use App\Services\PortfolioService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordPortfolioSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(PortfolioService $portfolioService, CMCClient $cmcClient): void
    {
        $portfolios = Portfolio::all();
        $coinData = $cmcClient->getCoinData();

        foreach ($portfolios as $portfolio) {
            $portfolioDetails = $portfolioService->loadPortfolio($portfolio)
                ->getPortfolioDetails($coinData);

            PortfolioSnapshot::create([
                'portfolio_id' => $portfolio->id,
                'total_portfolio_value' => $portfolioDetails['total_value'],
                'recorded_at' => now()->subMinutes(rand(0, 1440)),
            ]);
        }
    }
}
