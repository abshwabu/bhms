<?php

namespace App\Domain\Telegram\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessageLog extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'telegram_message_logs';

    protected $fillable = [
        'channel_id',
        'chat_id',
        'role',
        'message_type',
        'direction',
        'content',
        'parse_mode',
        'status',
        'retry_count',
        'max_retries',
        'error_message',
        'telegram_message_id',
        'response_payload',
        'sent_at',
        'failed_at',
    ];

    protected $casts = [
        'response_payload' => 'array',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(TelegramChannel::class, 'channel_id');
    }

    public function scopePendingRetry(Builder $query): Builder
    {
        return $query->whereIn('status', ['failed', 'retrying'])
            ->whereColumn('retry_count', '<', 'max_retries');
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }
}
