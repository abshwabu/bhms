<?php

namespace App\Domain\Administration\Models;

use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'notification_logs';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'template_id',
        'recipient_user_id',
        'channel',
        'recipient',
        'event_type',
        'subject',
        'body',
        'payload',
        'status',
        'provider',
        'retry_count',
        'max_retries',
        'error_message',
        'sent_at',
        'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function markSent(string $provider): self
    {
        $this->update([
            'status' => 'sent',
            'provider' => $provider,
            'sent_at' => Carbon::now(),
            'error_message' => null,
        ]);

        return $this;
    }

    public function markFailed(string $errorMessage, string $provider): self
    {
        $newRetryCount = $this->retry_count + 1;
        $status = ($newRetryCount >= $this->max_retries) ? 'failed' : 'retrying';

        $this->update([
            'status' => $status,
            'provider' => $provider,
            'retry_count' => $newRetryCount,
            'error_message' => $errorMessage,
            'failed_at' => Carbon::now(),
        ]);

        return $this;
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
