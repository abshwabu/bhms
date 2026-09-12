<?php

namespace App\Domain\Administration\Services\Notification\Providers;

use App\Domain\Administration\Models\NotificationLog;
use App\Domain\Administration\Services\Notification\Contracts\NotificationProviderInterface;
use Illuminate\Support\Str;

class EmailNotificationProvider implements NotificationProviderInterface
{
    public function getName(): string
    {
        return 'smtp_mail';
    }

    public function send(NotificationLog $log): array
    {
        if (!empty($log->payload['simulate_failure']) || !filter_var($log->recipient, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'provider' => $this->getName(),
                'error' => 'SMTP Transport Error: Invalid recipient mailbox or relay connection timed out.',
                'message_id' => null,
            ];
        }

        $messageId = 'MSG-' . strtoupper(Str::random(16)) . '@hms.local';

        return [
            'success' => true,
            'provider' => $this->getName(),
            'error' => null,
            'message_id' => $messageId,
        ];
    }
}
