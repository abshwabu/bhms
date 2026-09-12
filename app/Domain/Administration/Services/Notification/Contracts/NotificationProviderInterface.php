<?php

namespace App\Domain\Administration\Services\Notification\Contracts;

use App\Domain\Administration\Models\NotificationLog;

interface NotificationProviderInterface
{
    /**
     * Dispatch notification payload.
     *
     * @return array{success: bool, provider: string, error?: string|null, message_id?: string|null}
     */
    public function send(NotificationLog $log): array;

    /**
     * Get unique provider identifier.
     */
    public function getName(): string;
}
