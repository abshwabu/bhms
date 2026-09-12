<?php

namespace App\Domain\Administration\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTemplate extends BaseModel
{
    protected $table = 'notification_templates';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'code',
        'name',
        'channel',
        'subject',
        'body',
        'available_variables',
        'is_active',
    ];

    protected $casts = [
        'available_variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Render template subject and body by substituting merge variables.
     */
    public function render(array $variables = []): array
    {
        $renderedSubject = $this->subject;
        $renderedBody = $this->body;

        foreach ($variables as $key => $val) {
            $token = '{{' . $key . '}}';
            $valStr = (string) $val;
            if ($renderedSubject) {
                $renderedSubject = str_replace($token, $valStr, $renderedSubject);
            }
            $renderedBody = str_replace($token, $valStr, $renderedBody);
        }

        return [
            'subject' => $renderedSubject,
            'body' => $renderedBody,
        ];
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
