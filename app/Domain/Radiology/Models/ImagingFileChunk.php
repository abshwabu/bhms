<?php

namespace App\Domain\Radiology\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ImagingFileChunk extends Model
{
    use HasUuids;

    protected $table = 'imaging_file_chunks';

    protected $fillable = [
        'upload_id',
        'chunk_index',
        'total_chunks',
        'chunk_file_path',
        'chunk_size_bytes',
        'is_assembled',
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'total_chunks' => 'integer',
        'chunk_size_bytes' => 'integer',
        'is_assembled' => 'boolean',
    ];
}
