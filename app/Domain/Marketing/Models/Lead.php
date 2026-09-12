<?php

namespace App\Domain\Marketing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'leads';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'hospital_name',
        'hospital_type',
        'hospital_size',
        'branches_count',
        'modules_of_interest',
        'status',
        'preferred_demo_date',
        'notes',
        'source',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'modules_of_interest' => 'array',
        'branches_count' => 'integer',
        'preferred_demo_date' => 'date',
    ];

    public function scopeNewLeads($query)
    {
        return $query->where('status', 'new');
    }
}
