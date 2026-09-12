<?php

namespace App\Domain\Telegram\Http\Controllers;

use App\Domain\Administration\Models\Organization;
use App\Domain\Telegram\Models\TelegramChannel;
use App\Domain\Telegram\Services\TelegramApiService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TelegramChannelController extends Controller
{
    public function __construct(
        protected TelegramApiService $apiService
    ) {}

    /**
     * List all configured Telegram channels.
     */
    public function index(Request $request): JsonResponse
    {
        $query = TelegramChannel::query()->withCount('messageLogs');

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', $search)
                    ->orWhere('chat_id', 'ilike', $search)
                    ->orWhere('role', 'ilike', $search);
            });
        }

        $channels = $query->orderBy('name')->get();

        return response()->json([
            'data' => $channels,
            'meta' => [
                'total' => $channels->count(),
                'active_count' => $channels->where('is_active', true)->count(),
            ],
        ]);
    }

    /**
     * Store a new Telegram channel / group.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'chat_id' => 'required|string|max:100',
            'name' => 'required|string|max:150',
            'role' => 'required|string|in:admin,doctors,pharmacy,finance,nursing,emergency',
            'bot_token_ref' => 'nullable|string|max:100',
            'allowed_report_types' => 'nullable|array',
            'allowed_commands' => 'nullable|array',
            'alert_thresholds' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:255',
            'branch_id' => 'nullable|uuid',
            'organization_id' => 'nullable|uuid',
        ]);

        if (empty($validated['organization_id'])) {
            $validated['organization_id'] = Organization::first()?->id ?? (string) Str::uuid();
        }

        $channel = TelegramChannel::create([
            'organization_id' => $validated['organization_id'],
            'branch_id' => $validated['branch_id'] ?? null,
            'chat_id' => $validated['chat_id'],
            'name' => $validated['name'],
            'role' => $validated['role'],
            'bot_token_ref' => $validated['bot_token_ref'] ?? 'TELEGRAM_BOT_TOKEN',
            'allowed_report_types' => $validated['allowed_report_types'] ?? ['daily_digest', 'critical_alerts'],
            'allowed_commands' => $validated['allowed_commands'] ?? ['/help', '/beds', '/digest'],
            'alert_thresholds' => $validated['alert_thresholds'] ?? [
                'icu_bed_min' => 2,
                'low_stock_units' => 15,
                'critical_lab' => true,
            ],
            'is_active' => $validated['is_active'] ?? true,
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Telegram channel registered successfully.',
            'data' => $channel,
        ], 201);
    }

    /**
     * Show channel details.
     */
    public function show(string $id): JsonResponse
    {
        $channel = TelegramChannel::withCount('messageLogs')->findOrFail($id);

        return response()->json([
            'data' => $channel,
        ]);
    }

    /**
     * Update an existing Telegram channel.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $channel = TelegramChannel::findOrFail($id);

        $validated = $request->validate([
            'chat_id' => 'sometimes|required|string|max:100',
            'name' => 'sometimes|required|string|max:150',
            'role' => 'sometimes|required|string|in:admin,doctors,pharmacy,finance,nursing,emergency',
            'bot_token_ref' => 'nullable|string|max:100',
            'allowed_report_types' => 'nullable|array',
            'allowed_commands' => 'nullable|array',
            'alert_thresholds' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:255',
            'branch_id' => 'nullable|uuid',
        ]);

        $channel->update($validated);

        return response()->json([
            'message' => 'Telegram channel updated successfully.',
            'data' => $channel->fresh(),
        ]);
    }

    /**
     * Remove / soft-delete channel.
     */
    public function destroy(string $id): JsonResponse
    {
        $channel = TelegramChannel::findOrFail($id);
        $channel->delete();

        return response()->json([
            'message' => 'Telegram channel removed successfully.',
        ]);
    }

    /**
     * Send a test ping to verify Telegram chat connectivity.
     */
    public function testMessage(Request $request, string $id): JsonResponse
    {
        $channel = TelegramChannel::findOrFail($id);

        $testText = "🏥 <b>HMS Telegram Reporting - Test Ping</b>\n"
            . "──────────────────────────────\n"
            . "• <b>Channel:</b> {$channel->name}\n"
            . "• <b>Role:</b> <code>{$channel->role}</code>\n"
            . "• <b>Chat ID:</b> <code>{$channel->chat_id}</code>\n"
            . "• <b>Timestamp:</b> " . now()->format('Y-m-d H:i:s T') . "\n"
            . "──────────────────────────────\n"
            . "✅ <i>Connection verified successfully.</i>";

        $log = $this->apiService->sendMessage(
            chatId: $channel->chat_id,
            text: $testText,
            channel: $channel,
            messageType: 'custom'
        );

        return response()->json([
            'success' => $log->status === 'sent',
            'status' => $log->status,
            'message' => $log->status === 'sent' 
                ? 'Test message sent successfully!' 
                : 'Test message failed: ' . $log->error_message,
            'log' => $log,
        ]);
    }
}
