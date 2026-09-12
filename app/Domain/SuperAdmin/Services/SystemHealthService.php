<?php

namespace App\Domain\SuperAdmin\Services;

use App\Domain\Telegram\Models\TelegramMessageLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemHealthService
{
    /**
     * Compile real-time system health metrics.
     */
    public function getHealthOverview(): array
    {
        $now = now();

        // 1. Database Ping & latency
        $dbStatus = 'healthy';
        $dbLatencyMs = 0;
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $dbLatencyMs = round((microtime(true) - $start) * 1000, 2);
        } catch (\Throwable $e) {
            $dbStatus = 'degraded';
        }

        // 2. Queue & Failed Jobs inspection
        $failedJobsCount = 0;
        $recentFailedJobs = [];
        if (Schema::hasTable('failed_jobs')) {
            $failedJobsCount = DB::table('failed_jobs')->count();
            $recentFailedJobs = DB::table('failed_jobs')
                ->orderBy('failed_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'uuid' => $job->uuid,
                        'queue' => $job->queue,
                        'failed_at' => $job->failed_at,
                        'exception_preview' => substr($job->exception, 0, 160) . '...',
                    ];
                });
        }

        // 3. Background Telegram Messages Status (e.g. failed digests or alerts)
        $failedTelegramMessages = 0;
        $recentTelegramFailures = [];
        if (Schema::hasTable('telegram_message_logs')) {
            $failedTelegramMessages = TelegramMessageLog::whereIn('status', ['failed', 'retrying'])->count();
            $recentTelegramFailures = TelegramMessageLog::whereIn('status', ['failed', 'retrying'])
                ->with('channel:id,name,role,chat_id')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        // 4. Global Error Activity in last 24h
        $errorLogsCount = 0;
        if (Schema::hasTable('audit_logs')) {
            $errorLogsCount = DB::table('audit_logs')
                ->where('event', 'like', '%fail%')
                ->orWhere('event', 'like', '%error%')
                ->where('created_at', '>=', $now->copy()->subHours(24))
                ->count();
        }

        // 5. System Status Assessment
        $overallHealth = 'healthy';
        if ($dbStatus !== 'healthy' || $failedJobsCount > 10 || $failedTelegramMessages > 20) {
            $overallHealth = 'degraded';
        } elseif ($failedJobsCount > 0 || $failedTelegramMessages > 0) {
            $overallHealth = 'attention_needed';
        }

        return [
            'status' => $overallHealth,
            'timestamp' => $now->toIso8601String(),
            'database' => [
                'status' => $dbStatus,
                'latency_ms' => $dbLatencyMs,
                'driver' => config('database.default'),
            ],
            'queues' => [
                'failed_jobs_count' => $failedJobsCount,
                'recent_failures' => $recentFailedJobs,
            ],
            'background_jobs' => [
                'failed_telegram_messages_count' => $failedTelegramMessages,
                'recent_failures' => $recentTelegramFailures,
            ],
            'error_metrics' => [
                'errors_last_24h' => $errorLogsCount,
            ],
            'system_environment' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_time' => $now->toFormattedDateString() . ' ' . $now->format('H:i:s T'),
            ],
        ];
    }

    /**
     * Retry an individual failed queue job.
     */
    public function retryFailedJob(string|int $id): array
    {
        try {
            Artisan::call('queue:retry', ['id' => [$id]]);
            $output = trim(Artisan::output());

            return [
                'success' => true,
                'message' => $output ?: "Failed job #{$id} queued for retry.",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Purge all failed queue jobs.
     */
    public function flushFailedJobs(): array
    {
        try {
            Artisan::call('queue:flush');
            return [
                'success' => true,
                'message' => 'All failed jobs purged successfully.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
