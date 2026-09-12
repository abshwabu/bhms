<?php

namespace App\Domain\Administration\Services\Notification\Providers;

use App\Domain\Administration\Models\NotificationLog;
use App\Domain\Administration\Services\Notification\Contracts\NotificationProviderInterface;
use Illuminate\Support\Str;

class PushNotificationProvider implements NotificationProviderInterface
{
    public function getName(): string
    {
        return 'fcm_push';
    }

    public function send(NotificationLog $log): array
    {
        if (!empty($log->payload['simulate_failure']) || str_contains($log->recipient, 'expired_token')) {
            return [
                'success' => false,
                'provider' => $this->getName(),
                'error' => 'FCM Push Error: Registration token is unregistered or expired.',
                'message_id' => null,
            ];
        }

        $messageId = 'FCM-' . strtoupper(Str::random(20));

        return [
            'success' => true,
            'provider' => $this->getName(),
            'error' => null,
            'message_id' => $messageId,
        ];
    }
}
