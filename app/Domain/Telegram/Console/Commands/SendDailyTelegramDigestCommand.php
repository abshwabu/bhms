<?php

namespace App\Domain\Telegram\Console\Commands;

use App\Domain\Telegram\Models\TelegramChannel;
use App\Domain\Telegram\Services\TelegramApiService;
use App\Domain\Telegram\Services\TelegramReportGeneratorService;
use Illuminate\Console\Command;

class SendDailyTelegramDigestCommand extends Command
{
    protected $signature = 'hms:telegram-daily-digest {--branch= : Optional Branch UUID}';
    protected $description = 'Send daily operational digest to subscribed Telegram channels';

    public function handle(TelegramReportGeneratorService $reportGenerator, TelegramApiService $apiService): int
    {
        $branchId = $this->option('branch');
        $this->info("Generating Daily Operational Digest for Telegram...");

        $digest = $reportGenerator->generateDailyOperationalDigest($branchId);

        $channelsQuery = TelegramChannel::active();
        if ($branchId) {
            $channelsQuery->where('branch_id', $branchId);
        }

        $channels = $channelsQuery->get()->filter(fn($c) => $c->hasReportType('daily_digest'));

        if ($channels->isEmpty()) {
            $this->warn("No active Telegram channels subscribed to 'daily_digest'.");
            return self::SUCCESS;
        }

        $sentCount = 0;
        foreach ($channels as $channel) {
            $log = $apiService->sendMessage(
                chatId: $channel->chat_id,
                text: $digest['html'],
                channel: $channel,
                messageType: 'daily_digest'
            );

            if ($log->status === 'sent') {
                $sentCount++;
                $this->line("  [OK] Sent to {$channel->name} ({$channel->chat_id})");
            } else {
                $this->error("  [FAILED] Failed to send to {$channel->name}: {$log->error_message}");
            }
        }

        $this->info("Daily digest dispatched to {$sentCount}/{$channels->count()} channels.");
        return self::SUCCESS;
    }
}
