<?php

namespace App\Domain\Telegram\Services;

use App\Domain\Telegram\Models\TelegramChannel;
use App\Domain\Telegram\Models\TelegramMessageLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramApiService
{
    /**
     * Send a single message to a chat ID and log result.
     */
    public function sendMessage(
        string $chatId,
        string $text,
        ?TelegramChannel $channel = null,
        string $messageType = 'custom',
        string $parseMode = 'HTML',
        int $maxRetries = 3
    ): TelegramMessageLog {
        $log = TelegramMessageLog::create([
            'channel_id' => $channel?->id,
            'chat_id' => $chatId,
            'role' => $channel?->role,
            'message_type' => $messageType,
            'direction' => 'outbound',
            'content' => $text,
            'parse_mode' => $parseMode,
            'status' => 'queued',
            'retry_count' => 0,
            'max_retries' => $maxRetries,
        ]);

        return $this->dispatchTelegramHttp($log, $channel);
    }

    /**
     * Retry sending a previously failed message log entry.
     */
    public function retryLogEntry(TelegramMessageLog $log): TelegramMessageLog
    {
        if ($log->retry_count >= $log->max_retries) {
            $log->update(['status' => 'failed']);
            return $log;
        }

        $log->increment('retry_count');
        $channel = $log->channel;

        return $this->dispatchTelegramHttp($log, $channel);
    }

    /**
     * Execute HTTP request to Telegram Bot API.
     */
    protected function dispatchTelegramHttp(TelegramMessageLog $log, ?TelegramChannel $channel = null): TelegramMessageLog
    {
        $token = $this->resolveBotToken($channel);
        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        try {
            $response = Http::timeout(10)->post($url, [
                'chat_id' => $log->chat_id,
                'text' => $log->content,
                'parse_mode' => $log->parse_mode ?? 'HTML',
                'disable_web_page_preview' => true,
            ]);

            $payload = $response->json() ?? [];

            if ($response->successful() && ($payload['ok'] ?? false) === true) {
                $messageId = (string) ($payload['result']['message_id'] ?? '');
                $log->update([
                    'status' => 'sent',
                    'telegram_message_id' => $messageId,
                    'response_payload' => $payload,
                    'sent_at' => now(),
                    'error_message' => null,
                ]);
            } else {
                $error = $payload['description'] ?? ('HTTP ' . $response->status() . ': ' . $response->body());
                $status = ($log->retry_count < $log->max_retries) ? 'retrying' : 'failed';

                $log->update([
                    'status' => $status,
                    'response_payload' => $payload,
                    'failed_at' => now(),
                    'error_message' => $error,
                ]);

                Log::warning("Telegram message dispatch failed for chat {$log->chat_id}: {$error}");
            }
        } catch (Throwable $e) {
            $status = ($log->retry_count < $log->max_retries) ? 'retrying' : 'failed';
            $log->update([
                'status' => $status,
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            Log::error("Telegram API exception for chat {$log->chat_id}: " . $e->getMessage());
        }

        return $log->fresh();
    }

    /**
     * Resolve Bot token for channel or default.
     */
    public function resolveBotToken(?TelegramChannel $channel = null): string
    {
        if ($channel && !empty($channel->bot_token_ref)) {
            $envToken = env($channel->bot_token_ref);
            if (!empty($envToken)) {
                return $envToken;
            }
        }

        return config('services.telegram.bot_token')
            ?? env('TELEGRAM_BOT_TOKEN')
            ?? 'fake-telegram-token';
    }
}
