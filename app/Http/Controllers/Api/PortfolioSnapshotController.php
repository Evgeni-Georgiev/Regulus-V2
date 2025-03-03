<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use Illuminate\Http\JsonResponse;

class PortfolioSnapshotController extends Controller
{
    public function show(Portfolio $portfolio): JsonResponse
    {
        $portfolioSnapshots = $portfolio->snapshots()
            ->orderBy('recorded_at')
            ->get()
            ->map(function ($snapshot) {
                return [
                    'recorded_at' => $snapshot->recorded_at->format('Y-m-d H:i:s'),
                    'total_portfolio_value' => (float)$snapshot->total_portfolio_value,
                ];
            });

        return response()->json([
            'data' => $portfolioSnapshots,
            'total_portfolio_value_sum' => (float)$portfolioSnapshots->sum('total_portfolio_value'),
        ]);
    }
}
