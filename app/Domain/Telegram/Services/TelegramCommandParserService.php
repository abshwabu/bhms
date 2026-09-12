<?php

namespace App\Domain\Telegram\Services;

use App\Domain\Telegram\Models\TelegramChannel;
use App\Domain\Telegram\Models\TelegramMessageLog;

class TelegramCommandParserService
{
    public function __construct(
        protected TelegramReportGeneratorService $reportGenerator,
        protected TelegramApiService $apiService
    ) {}

    /**
     * Process an inbound command received from Telegram webhook or simulation.
     */
    public function processCommand(string $chatId, string $text, ?string $senderName = null): array
    {
        $text = trim($text);
        $parts = preg_split('/\s+/', $text);
        $commandRoot = strtolower($parts[0] ?? '');
        $param = strtolower($parts[1] ?? '');

        // 1. Find active channel
        $channel = TelegramChannel::where('chat_id', $chatId)->where('is_active', true)->first();

        // 2. Log inbound command
        TelegramMessageLog::create([
            'channel_id' => $channel?->id,
            'chat_id' => $chatId,
            'role' => $channel?->role,
            'message_type' => 'command_response',
            'direction' => 'inbound_command',
            'content' => $text,
            'parse_mode' => 'HTML',
            'status' => 'sent',
            'sent_at' => now(),
            'response_payload' => ['sender' => $senderName, 'command' => $text],
        ]);

        // 3. Reject unregistered channels
        if (!$channel) {
            $unregisteredMsg = "⚠️ <b>Access Denied: Unregistered Channel</b>\n"
                . "This Telegram chat (ID: <code>{$chatId}</code>) is not registered in the Hospital Management System.\n"
                . "Please contact your HMS System Administrator to register and authorize this channel.";

            $this->apiService->sendMessage($chatId, $unregisteredMsg, null, 'command_response');

            return [
                'authorized' => false,
                'status' => 'unregistered_chat',
                'chat_id' => $chatId,
                'response' => $unregisteredMsg,
            ];
        }

        // 4. Role-based Command Authorization check
        $authCheck = $this->authorizeCommand($channel, $commandRoot);
        if (!$authCheck['authorized']) {
            $deniedMsg = "⛔ <b>Access Denied</b>\n"
                . $authCheck['reason'] . "\n"
                . "Use /help to see commands available to your role (<code>{$channel->role}</code>).";

            $this->apiService->sendMessage($chatId, $deniedMsg, $channel, 'command_response');

            return [
                'authorized' => false,
                'status' => 'forbidden',
                'role' => $channel->role,
                'response' => $deniedMsg,
            ];
        }

        // 5. Execute Command
        $responseHtml = match ($commandRoot) {
            '/revenue' => $this->reportGenerator->generateRevenueReport($param ?: 'today', $channel->branch_id),
            '/beds' => $this->reportGenerator->generateBedOccupancyReport($channel->branch_id),
            '/stock' => $this->reportGenerator->generatePharmacyStockReport($channel->branch_id),
            '/patients' => $this->reportGenerator->generatePatientCensusReport($channel->branch_id),
            '/handover' => $this->reportGenerator->generateShiftHandoverReport($param ?: 'morning', $channel->branch_id)['html'],
            '/digest' => $this->reportGenerator->generateDailyOperationalDigest($channel->branch_id)['html'],
            '/start', '/help' => $this->generateHelpResponse($channel),
            default => "❓ <b>Unknown Command:</b> <code>{$commandRoot}</code>\nType /help to see the list of valid commands.",
        };

        // 6. Send reply via Telegram API
        $outboundLog = $this->apiService->sendMessage($chatId, $responseHtml, $channel, 'command_response');

        return [
            'authorized' => true,
            'status' => 'success',
            'channel' => $channel->name,
            'role' => $channel->role,
            'command' => $commandRoot,
            'response' => $responseHtml,
            'log_id' => $outboundLog->id,
        ];
    }

    /**
     * RBAC matrix evaluation for command execution.
     */
    protected function authorizeCommand(TelegramChannel $channel, string $commandRoot): array
    {
        // Check if command is explicitly permitted in channel's allowed_commands array
        $allowedCommands = array_map('strtolower', $channel->allowed_commands ?? []);
        $hasCommandPermission = in_array($commandRoot, $allowedCommands, true) || in_array('*', $allowedCommands, true);

        // Always permit /help and /start
        if (in_array($commandRoot, ['/help', '/start'], true)) {
            return ['authorized' => true];
        }

        // Define Role Restrictions
        $role = strtolower($channel->role);

        $roleAllowedMatrix = [
            '/revenue' => ['finance', 'admin'],
            '/stock' => ['pharmacy', 'admin', 'doctors'],
            '/beds' => ['doctors', 'nursing', 'admin', 'emergency'],
            '/patients' => ['doctors', 'nursing', 'admin', 'emergency'],
            '/handover' => ['nursing', 'doctors', 'admin'],
            '/digest' => ['admin', 'doctors', 'finance', 'nursing', 'pharmacy', 'emergency'],
        ];

        if (isset($roleAllowedMatrix[$commandRoot])) {
            $requiredRoles = $roleAllowedMatrix[$commandRoot];
            if (!in_array($role, $requiredRoles, true) && $role !== 'admin') {
                return [
                    'authorized' => false,
                    'reason' => "Role [<b>{$role}</b>] is not permitted to query <code>{$commandRoot}</code>.",
                ];
            }
        }

        if (!$hasCommandPermission) {
            return [
                'authorized' => false,
                'reason' => "Command <code>{$commandRoot}</code> is not in this channel's configured permission list.",
            ];
        }

        return ['authorized' => true];
    }

    /**
     * Generate dynamic help response reflecting channel's role and allowed commands.
     */
    protected function generateHelpResponse(TelegramChannel $channel): string
    {
        $role = ucfirst($channel->role);
        $allowed = $channel->allowed_commands ?? [];

        $html = "🤖 <b>HMS HOSPITAL BOT COMMANDS</b>\n"
            . "🏢 <b>Channel:</b> {$channel->name} (Role: <code>{$role}</code>)\n"
            . "──────────────────────────────\n";

        $commandDocs = [
            '/digest' => 'Push the complete daily operational digest',
            '/beds' => 'Query current bed occupancy & ICU availability',
            '/patients' => 'View current inpatient and emergency census',
            '/revenue [today|month]' => 'Financial metrics (Finance/Admin only)',
            '/stock [low]' => 'Pharmacy inventory & reorder levels (Pharmacy/Admin)',
            '/handover [morning|evening|night]' => 'Clinical shift handover briefing',
            '/help' => 'Show this command reference list',
        ];

        foreach ($commandDocs as $cmd => $desc) {
            $root = explode(' ', $cmd)[0];
            $canRun = in_array($root, array_map('strtolower', $allowed), true) || in_array('*', $allowed, true);
            $icon = $canRun ? "✅" : "🔒";
            $html .= "{$icon} <code>{$cmd}</code>\n   <i>{$desc}</i>\n";
        }

        $html .= "──────────────────────────────\n"
            . "<i>Commands with 🔒 are restricted for your channel's role.</i>";

        return $html;
    }
}
