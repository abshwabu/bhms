<?php

namespace App\Domain\Telegram\Console\Commands;

use App\Domain\Telegram\Models\TelegramMessageLog;
use App\Domain\Telegram\Services\TelegramApiService;
use Illuminate\Console\Command;

class RetryFailedTelegramMessagesCommand extends Command
{
    protected $signature = 'hms:telegram-retry-failed {--limit=50 : Maximum messages to retry}';
    protected $description = 'Retry failed or pending-retry Telegram outbound messages with exponential backoff';

    public function handle(TelegramApiService $apiService): int
    {
        $limit = (int) $this->option('limit');
        $this->info("Checking for failed Telegram messages eligible for retry...");

        $pendingLogs = TelegramMessageLog::pendingRetry()
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        if ($pendingLogs->isEmpty()) {
            $this->info("No failed messages currently pending retry.");
            return self::SUCCESS;
        }

        $this->info("Found {$pendingLogs->count()} message(s) to retry.");
        $successCount = 0;
        $failedCount = 0;

        foreach ($pendingLogs as $log) {
            $this->line("Retrying message #{$log->id} to chat {$log->chat_id} (Attempt {$log->retry_count}/{$log->max_retries})...");
            $updatedLog = $apiService->retryLogEntry($log);

            if ($updatedLog->status === 'sent') {
                $successCount++;
                $this->info("  -> Successfully sent on retry!");
            } else {
                $failedCount++;
                $this->warn("  -> Retry failed: {$updatedLog->error_message} (status: {$updatedLog->status})");
            }
        }

        $this->info("Retry cycle completed. Sent: {$successCount}, Still Failing/Retrying: {$failedCount}");
        return self::SUCCESS;
    }
}
