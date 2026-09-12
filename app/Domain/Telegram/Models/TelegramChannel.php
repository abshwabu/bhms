<?php

namespace App\Domain\Telegram\Models;

use App\Domain\Administration\Models\Branch;
use App\Domain\Administration\Models\Organization;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TelegramChannel extends BaseModel
{
    use BelongsToBranch, SoftDeletes;

    protected $table = 'telegram_channels';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'chat_id',
        'name',
        'role',
        'bot_token_ref',
        'allowed_report_types',
        'allowed_commands',
        'alert_thresholds',
        'is_active',
        'description',
    ];

    protected $casts = [
        'allowed_report_types' => 'array',
        'allowed_commands' => 'array',
        'alert_thresholds' => 'array',
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(TelegramMessageLog::class, 'channel_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    /**
     * Check if channel is authorized for a report type.
     */
    public function hasReportType(string $reportType): bool
    {
        $types = $this->allowed_report_types ?? [];
        return in_array($reportType, $types, true) || in_array('*', $types, true);
    }

    /**
     * Check if channel role is authorized to execute a bot command.
     */
    public function canExecuteCommand(string $command): bool
    {
        $command = strtolower(trim($command));
        // Normalize command root (e.g. /revenue today -> /revenue)
        $commandRoot = explode(' ', $command)[0];

        $allowed = array_map('strtolower', $this->allowed_commands ?? []);
        return in_array($commandRoot, $allowed, true) || in_array('*', $allowed, true);
    }
}
