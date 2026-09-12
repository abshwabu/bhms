<?php

namespace App\Domain\Administration\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'backup_logs';

    protected $fillable = [
        'backup_type',
        'file_path',
        'file_size_bytes',
        'status',
        'checksum_sha256',
        'metadata',
        'verified_at',
        'restored_at',
        'notes',
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'metadata' => 'array',
        'verified_at' => 'datetime',
        'restored_at' => 'datetime',
    ];

    public function markVerified(): self
    {
        $this->update([
            'status' => 'verified',
            'verified_at' => Carbon::now(),
        ]);

        return $this;
    }

    public function markRestored(string $notes = ''): self
    {
        $this->update([
            'status' => 'restored',
            'restored_at' => Carbon::now(),
            'notes' => $notes ?: $this->notes,
        ]);

        return $this;
    }
}
