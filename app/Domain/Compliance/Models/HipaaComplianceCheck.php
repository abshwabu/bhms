<?php

namespace App\Domain\Compliance\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HipaaComplianceCheck extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'hipaa_compliance_checks';

    protected $fillable = [
        'check_category',
        'safeguard_code',
        'title',
        'description',
        'status',
        'details',
        'remediation_steps',
        'last_evaluated_at',
    ];

    protected $casts = [
        'details' => 'array',
        'last_evaluated_at' => 'datetime',
    ];

    public function markEvaluated(string $status, array $details = [], ?string $remediationSteps = null): self
    {
        $this->update([
            'status' => $status,
            'details' => $details,
            'remediation_steps' => $remediationSteps,
            'last_evaluated_at' => Carbon::now(),
        ]);

        return $this;
    }
}
