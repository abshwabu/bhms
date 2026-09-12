<?php

namespace Database\Seeders;

use App\Domain\Reports\Services\KpiAggregationService;
use App\Domain\Shared\Models\Branch;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReportsAnalyticsModuleSeeder extends Seeder
{
    /**
     * Seed Reports & Analytics module by pre-aggregating past 30 days of metrics.
     */
    public function run(KpiAggregationService $aggregationService): void
    {
        $branches = Branch::all();
        if ($branches->isEmpty()) {
            return;
        }

        $startDate = Carbon::today()->subDays(30);
        $endDate = Carbon::today();

        foreach ($branches as $branch) {
            $aggregationService->backfillRange($branch->id, $startDate, $endDate);
        }
    }
}
