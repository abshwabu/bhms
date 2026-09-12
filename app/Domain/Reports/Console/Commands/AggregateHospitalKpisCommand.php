<?php

namespace App\Domain\Reports\Console\Commands;

use App\Domain\Reports\Services\KpiAggregationService;
use App\Domain\Shared\Models\Branch;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AggregateHospitalKpisCommand extends Command
{
    protected $signature = 'reports:aggregate 
                            {--branch= : Target branch ID} 
                            {--date= : Specific date to aggregate (YYYY-MM-DD)} 
                            {--days=7 : Number of past days to aggregate}';

    protected $description = 'Pre-aggregate hospital KPIs, department metrics, and doctor performance for high-speed reporting.';

    public function handle(KpiAggregationService $aggregationService): int
    {
        $this->info('Starting hospital KPI pre-aggregation job...');

        $branchId = $this->option('branch');
        $branches = $branchId
            ? Branch::where('id', $branchId)->get()
            : Branch::all();

        if ($branches->isEmpty()) {
            // Default demo branch if none in DB
            $branches = collect([(object)['id' => '84d7387e-7b2e-4533-b3e8-139e52aecb8b', 'name' => 'Main Hospital Branch']]);
        }

        $specificDate = $this->option('date');
        $days = (int) $this->option('days');

        foreach ($branches as $branch) {
            $this->line("Processing branch: {$branch->name} ({$branch->id})");

            if ($specificDate) {
                $target = Carbon::parse($specificDate);
                $aggregationService->aggregateForDate($branch->id, $target);
                $aggregationService->aggregateDepartmentsForDate($branch->id, $target);
                $aggregationService->aggregateDoctorsForDate($branch->id, $target);
                $this->info(" -> Completed for {$target->format('Y-m-d')}");
            } else {
                $start = Carbon::today()->subDays($days - 1);
                $end = Carbon::today();
                $count = $aggregationService->backfillRange($branch->id, $start, $end);
                $this->info(" -> Aggregated {$count} days (from {$start->format('Y-m-d')} to {$end->format('Y-m-d')})");
            }
        }

        $this->info('KPI pre-aggregation completed successfully.');
        return self::SUCCESS;
    }
}
