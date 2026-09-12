<?php

namespace App\Domain\Administration\Services\Notification\Providers;

use App\Domain\Administration\Models\NotificationLog;
use App\Domain\Administration\Services\Notification\Contracts\NotificationProviderInterface;
use Illuminate\Support\Str;

class SmsNotificationProvider implements NotificationProviderInterface
{
    public function getName(): string
    {
        return 'twilio_sms';
    }

    public function send(NotificationLog $log): array
    {
        // Support testing failure scenario via payload flag or dummy invalid number
        if (!empty($log->payload['simulate_failure']) || str_contains($log->recipient, 'invalid')) {
            return [
                'success' => false,
                'provider' => $this->getName(),
                'error' => 'SMS Gateway Error: Carrier unreachable or invalid mobile destination.',
                'message_id' => null,
            ];
        }

        // Simulates production SMS gateway transmission (Twilio / Africa's Talking)
        $messageId = 'SMS-' . strtoupper(Str::random(12));

        return [
            'success' => true,
            'provider' => $this->getName(),
            'error' => null,
            'message_id' => $messageId,
        ];
    }
}
