<?php

namespace App\Domain\SuperAdmin\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends BaseModel
{
    use SoftDeletes;

    protected $table = 'support_tickets';

    protected $fillable = [
        'ticket_number',
        'organization_id',
        'reporter_user_id',
        'reporter_name',
        'reporter_email',
        'subject',
        'description',
        'category',
        'priority',
        'status',
        'assigned_to',
        'resolution_notes',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
