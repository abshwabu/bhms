<?php

namespace App\Domain\Telegram\Http\Controllers;

use App\Domain\Telegram\Models\TelegramChannel;
use App\Domain\Telegram\Models\TelegramMessageLog;
use App\Domain\Telegram\Services\TelegramAlertDispatcherService;
use App\Domain\Telegram\Services\TelegramApiService;
use App\Domain\Telegram\Services\TelegramCommandParserService;
use App\Domain\Telegram\Services\TelegramReportGeneratorService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramReportController extends Controller
{
    public function __construct(
        protected TelegramReportGeneratorService $reportGenerator,
        protected TelegramAlertDispatcherService $alertDispatcher,
        protected TelegramCommandParserService $commandParser,
        protected TelegramApiService $apiService
    ) {}

    /**
     * Trigger and dispatch Daily Operational Digest.
     */
    public function triggerDailyDigest(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id');
        $digest = $this->reportGenerator->generateDailyOperationalDigest($branchId);

        $channels = TelegramChannel::active()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->filter(fn($c) => $c->hasReportType('daily_digest'));

        $results = [];
        foreach ($channels as $channel) {
            $log = $this->apiService->sendMessage(
                chatId: $channel->chat_id,
                text: $digest['html'],
                channel: $channel,
                messageType: 'daily_digest'
            );

            $results[] = [
                'channel' => $channel->name,
                'chat_id' => $channel->chat_id,
                'status' => $log->status,
                'log_id' => $log->id,
            ];
        }

        return response()->json([
            'message' => 'Daily Operational Digest triggered.',
            'dispatched_count' => count($results),
            'results' => $results,
            'preview_html' => $digest['html'],
            'raw_data' => $digest['raw_data'],
        ]);
    }

    /**
     * Trigger and dispatch Shift Handover Report.
     */
    public function triggerShiftHandover(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shift' => 'required|string|in:morning,evening,night',
            'branch_id' => 'nullable|uuid',
        ]);

        $shift = $validated['shift'];
        $branchId = $validated['branch_id'] ?? null;

        $report = $this->reportGenerator->generateShiftHandoverReport($shift, $branchId);

        $channels = TelegramChannel::active()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->filter(fn($c) => $c->hasReportType('shift_handover') || in_array($c->role, ['nursing', 'doctors', 'admin'], true));

        $results = [];
        foreach ($channels as $channel) {
            $log = $this->apiService->sendMessage(
                chatId: $channel->chat_id,
                text: $report['html'],
                channel: $channel,
                messageType: 'shift_handover'
            );

            $results[] = [
                'channel' => $channel->name,
                'chat_id' => $channel->chat_id,
                'status' => $log->status,
                'log_id' => $log->id,
            ];
        }

        return response()->json([
            'message' => "Shift Handover report ({$shift}) dispatched.",
            'dispatched_count' => count($results),
            'results' => $results,
            'preview_html' => $report['html'],
        ]);
    }

    /**
     * Trigger real-time critical alert dispatch.
     */
    public function triggerCriticalAlert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'alert_type' => 'required|string|in:emergency_esi1,critical_lab,icu_shortage,low_stock',
            'data' => 'nullable|array',
            'branch_id' => 'nullable|uuid',
        ]);

        $alertType = $validated['alert_type'];
        $data = $validated['data'] ?? [];
        $branchId = $validated['branch_id'] ?? null;

        // Default sample payload if empty
        if (empty($data)) {
            $data = match ($alertType) {
                'emergency_esi1' => [
                    'case_number' => 'EM-2026-' . rand(1000, 9999),
                    'patient_name' => 'Emergency Trauma Patient',
                    'age' => '38',
                    'gender' => 'Male',
                    'chief_complaint' => 'Acute severe polytrauma, hypotension, altered consciousness',
                    'location' => 'Resuscitation Suite 1',
                ],
                'critical_lab' => [
                    'report_number' => 'LAB-' . rand(10000, 99999),
                    'patient_name' => 'Sarah Johnson',
                    'test_name' => 'Cardiac Panel / High Sensitivity Troponin',
                    'parameter' => 'Troponin I',
                    'value' => '1.45 ng/mL',
                    'reference_range' => '< 0.04 ng/mL',
                    'doctor_name' => 'Dr. Robert Martinez (Cardiology)',
                ],
                'icu_shortage' => [
                    'available_icu' => 1,
                    'total_icu' => 12,
                ],
                'low_stock' => [
                    'drug_name' => 'Atropine Sulfate 1mg/mL Inj',
                    'current_stock' => 3,
                    'reorder_threshold' => 20,
                ],
                default => ['message' => 'Test critical alert notice'],
            };
        }

        $result = $this->alertDispatcher->dispatchCriticalAlert($alertType, $data, $branchId);

        return response()->json([
            'message' => 'Critical alert dispatched to authorized channels.',
            'alert_type' => $alertType,
            'dispatched_count' => $result['dispatched_count'],
            'dispatches' => $result['dispatches'],
            'message_preview' => $result['message_preview'],
        ]);
    }

    /**
     * Simulate a bot command execution from the Admin console.
     */
    public function simulateCommand(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'chat_id' => 'required|string',
            'command' => 'required|string',
            'sender_name' => 'nullable|string',
        ]);

        $result = $this->commandParser->processCommand(
            chatId: $validated['chat_id'],
            text: $validated['command'],
            senderName: $validated['sender_name'] ?? 'Admin Simulator'
        );

        return response()->json([
            'result' => $result,
        ]);
    }

    /**
     * List message audit logs with filters.
     */
    public function logs(Request $request): JsonResponse
    {
        $query = TelegramMessageLog::with('channel:id,name,role,chat_id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('message_type')) {
            $query->where('message_type', $request->input('message_type'));
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->input('direction'));
        }

        if ($request->filled('chat_id')) {
            $query->where('chat_id', 'ilike', '%' . $request->input('chat_id') . '%');
        }

        $perPage = min(100, (int) $request->input('per_page', 25));
        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Retry a specific failed message log.
     */
    public function retryMessage(string $id): JsonResponse
    {
        $log = TelegramMessageLog::findOrFail($id);

        $updatedLog = $this->apiService->retryLogEntry($log);

        return response()->json([
            'message' => $updatedLog->status === 'sent' 
                ? 'Message sent successfully on retry.' 
                : 'Retry failed: ' . $updatedLog->error_message,
            'data' => $updatedLog,
        ]);
    }
}
