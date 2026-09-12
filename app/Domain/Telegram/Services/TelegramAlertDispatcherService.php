<?php

namespace App\Domain\Telegram\Services;

use App\Domain\Telegram\Models\TelegramChannel;
use App\Domain\Telegram\Models\TelegramMessageLog;

class TelegramAlertDispatcherService
{
    public function __construct(
        protected TelegramApiService $apiService
    ) {}

    /**
     * Dispatch an unbatched real-time critical alert to all eligible Telegram channels.
     */
    public function dispatchCriticalAlert(string $alertType, array $data, ?string $branchId = null): array
    {
        $messageHtml = $this->formatAlertHtml($alertType, $data);
        $targetRoles = $this->getTargetRolesForAlertType($alertType);

        // Fetch active channels subscribed to critical_alerts with matching roles
        $channelsQuery = TelegramChannel::active();
        if ($branchId) {
            $channelsQuery->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            });
        }

        $allChannels = $channelsQuery->get();
        $dispatchedLogs = [];

        foreach ($allChannels as $channel) {
            // Check subscription to critical alerts
            if (!$channel->hasReportType('critical_alerts')) {
                continue;
            }

            // Role matching: admin always receives, or role must be in targetRoles
            if ($channel->role !== 'admin' && !in_array($channel->role, $targetRoles, true)) {
                continue;
            }

            // Threshold filter for specific alerts if customized on channel
            if ($alertType === 'icu_shortage') {
                $customThreshold = $channel->alert_thresholds['icu_bed_min'] ?? 2;
                $currentAvailable = $data['available_icu'] ?? 0;
                if ($currentAvailable > $customThreshold) {
                    continue; // Do not alert if above this channel's custom threshold
                }
            }

            // Dispatch immediately (unbatched real-time push)
            $log = $this->apiService->sendMessage(
                chatId: $channel->chat_id,
                text: $messageHtml,
                channel: $channel,
                messageType: 'critical_alert'
            );

            $dispatchedLogs[] = [
                'channel_id' => $channel->id,
                'chat_id' => $channel->chat_id,
                'role' => $channel->role,
                'status' => $log->status,
                'log_id' => $log->id,
            ];
        }

        return [
            'alert_type' => $alertType,
            'dispatched_count' => count($dispatchedLogs),
            'dispatches' => $dispatchedLogs,
            'message_preview' => $messageHtml,
        ];
    }

    /**
     * Determine permitted target channel roles per alert type.
     */
    protected function getTargetRolesForAlertType(string $alertType): array
    {
        return match ($alertType) {
            'emergency_esi1' => ['emergency', 'doctors', 'nursing', 'admin'],
            'critical_lab' => ['doctors', 'nursing', 'admin'],
            'icu_shortage' => ['doctors', 'nursing', 'emergency', 'admin'],
            'low_stock' => ['pharmacy', 'admin'],
            default => ['admin'],
        };
    }

    /**
     * Format critical alert HTML for Telegram display.
     */
    protected function formatAlertHtml(string $alertType, array $data): string
    {
        $timestamp = now()->format('Y-m-d H:i:s T');

        return match ($alertType) {
            'emergency_esi1' => "🚨 <b>CRITICAL EMERGENCY ALERT: ESI-1 RESUSCITATION</b>\n"
                . "📅 <i>{$timestamp}</i>\n"
                . "──────────────────────────────\n"
                . "• <b>Case #:</b> <code>" . ($data['case_number'] ?? 'N/A') . "</code>\n"
                . "• <b>Patient:</b> " . ($data['patient_name'] ?? 'Unidentified') . " (" . ($data['age'] ?? 'Adult') . ", " . ($data['gender'] ?? 'Unknown') . ")\n"
                . "• <b>Chief Complaint:</b> <b>" . ($data['chief_complaint'] ?? 'Cardiac Arrest / Respiratory Failure') . "</b>\n"
                . "• <b>Location:</b> " . ($data['location'] ?? 'Emergency Resuscitation Bay 1') . "\n"
                . "──────────────────────────────\n"
                . "⚠️ <i>All available Code Blue / Trauma team report immediately.</i>",

            'critical_lab' => "⚠️ <b>CRITICAL PANIC LAB RESULT DETECTED</b>\n"
                . "📅 <i>{$timestamp}</i>\n"
                . "──────────────────────────────\n"
                . "• <b>Report #:</b> <code>" . ($data['report_number'] ?? 'LAB-ALERT') . "</code>\n"
                . "• <b>Patient:</b> " . ($data['patient_name'] ?? 'Patient Record') . "\n"
                . "• <b>Test Name:</b> " . ($data['test_name'] ?? 'Comprehensive Chemistry') . "\n"
                . "• <b>Panic Value:</b> 🔴 <b>" . ($data['parameter'] ?? 'Parameter') . ": " . ($data['value'] ?? '') . "</b> (Ref: " . ($data['reference_range'] ?? 'Standard') . ")\n"
                . "• <b>Ordering Physician:</b> " . ($data['doctor_name'] ?? 'Attending Physician') . "\n"
                . "──────────────────────────────\n"
                . "⚠️ <i>Immediate physician acknowledgment and clinical action required.</i>",

            'icu_shortage' => "🛑 <b>ICU BED CAPACITY SHORTAGE ALERT</b>\n"
                . "📅 <i>{$timestamp}</i>\n"
                . "──────────────────────────────\n"
                . "• <b>Available ICU Beds:</b> 🔴 <b>" . ($data['available_icu'] ?? 0) . "</b>\n"
                . "• <b>Total ICU Capacity:</b> " . ($data['total_icu'] ?? 10) . "\n"
                . "• <b>Status:</b> Capacity threshold breached (<= 2 beds available)\n"
                . "──────────────────────────────\n"
                . "⚠️ <i>Coordinate step-downs or initiate transfer protocol.</i>",

            'low_stock' => "💊 <b>PHARMACY CRITICAL LOW STOCK ALERT</b>\n"
                . "📅 <i>{$timestamp}</i>\n"
                . "──────────────────────────────\n"
                . "• <b>Medication:</b> <b>" . ($data['drug_name'] ?? 'Vital Drug') . "</b>\n"
                . "• <b>Current Stock:</b> 🔴 <code>" . ($data['current_stock'] ?? 0) . " units</code>\n"
                . "• <b>Reorder Threshold:</b> <code>" . ($data['reorder_threshold'] ?? 10) . " units</code>\n"
                . "──────────────────────────────\n"
                . "⚠️ <i>Urgent pharmacy purchase order required.</i>",

            default => "🔔 <b>HOSPITAL PRIORITY ALERT</b>\n"
                . "📅 <i>{$timestamp}</i>\n"
                . "──────────────────────────────\n"
                . ($data['message'] ?? 'General priority alert notice.') . "\n"
                . "──────────────────────────────",
        };
    }
}
