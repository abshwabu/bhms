<?php

namespace App\Domain\Telegram\Http\Controllers;

use App\Domain\Telegram\Services\TelegramCommandParserService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(
        protected TelegramCommandParserService $commandParser
    ) {}

    /**
     * Handle inbound Telegram Webhook payloads.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info("Inbound Telegram Webhook received", ['payload' => $payload]);

        // Telegram Bot API sends update with message / edited_message / channel_post
        $message = $payload['message'] ?? $payload['channel_post'] ?? $payload['edited_message'] ?? null;

        if (!$message) {
            return response()->json(['ok' => true, 'message' => 'No message payload found']);
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        $text = (string) ($message['text'] ?? '');
        $username = $message['from']['username'] ?? $message['from']['first_name'] ?? 'Staff User';

        if (empty($chatId) || empty($text)) {
            return response()->json(['ok' => true, 'message' => 'Ignored non-text or empty chat update']);
        }

        // Process command
        $result = $this->commandParser->processCommand($chatId, $text, $username);

        return response()->json([
            'ok' => true,
            'result' => $result,
        ]);
    }
}
