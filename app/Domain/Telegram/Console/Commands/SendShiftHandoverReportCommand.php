<?php

namespace App\Domain\Telegram\Console\Commands;

use App\Domain\Telegram\Models\TelegramChannel;
use App\Domain\Telegram\Services\TelegramApiService;
use App\Domain\Telegram\Services\TelegramReportGeneratorService;
use Illuminate\Console\Command;

class SendShiftHandoverReportCommand extends Command
{
    protected $signature = 'hms:telegram-shift-handover {shift=morning : Shift period (morning, evening, night)} {--branch= : Optional Branch UUID}';
    protected $description = 'Send nursing and clinical shift handover briefing to Telegram channels';

    public function handle(TelegramReportGeneratorService $reportGenerator, TelegramApiService $apiService): int
    {
        $shift = strtolower($this->argument('shift'));
        $branchId = $this->option('branch');

        $this->info("Compiling {$shift} Shift Handover Report...");
        $report = $reportGenerator->generateShiftHandoverReport($shift, $branchId);

        $channels = TelegramChannel::active()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->filter(fn($c) => $c->hasReportType('shift_handover') || in_array($c->role, ['nursing', 'doctors', 'admin'], true));

        if ($channels->isEmpty()) {
            $this->warn("No active Telegram channels found for shift handover.");
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($channels as $channel) {
            $log = $apiService->sendMessage(
                chatId: $channel->chat_id,
                text: $report['html'],
                channel: $channel,
                messageType: 'shift_handover'
            );

            if ($log->status === 'sent') {
                $count++;
                $this->line("  [OK] Shift report delivered to {$channel->name} ({$channel->chat_id})");
            }
        }

        $this->info("Handover report dispatched to {$count} channels.");
        return self::SUCCESS;
    }
}
