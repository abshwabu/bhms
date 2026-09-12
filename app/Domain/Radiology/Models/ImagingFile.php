<?php

namespace App\Domain\Radiology\Models;

use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ImagingFile extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'imaging_files';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'imaging_order_id',
        'imaging_report_id',
        'patient_id',
        'file_name',
        'original_file_name',
        'file_path',
        'disk',
        'mime_type',
        'file_size_bytes',
        'is_dicom',
        'dicom_sop_instance_uid',
        'series_description',
        'series_number',
        'instance_number',
        'window_center',
        'window_width',
        'pacs_wado_url',
        'pacs_preview_url',
        'thumbnail_path',
        'metadata',
        'upload_status',
        'uploaded_by',
    ];

    protected $casts = [
        'is_dicom' => 'boolean',
        'file_size_bytes' => 'integer',
        'series_number' => 'integer',
        'instance_number' => 'integer',
        'window_center' => 'float',
        'window_width' => 'float',
        'metadata' => 'array',
    ];

    protected $appends = [
        'file_url',
        'thumbnail_url',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ImagingOrder::class, 'imaging_order_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ImagingReport::class, 'imaging_report_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute(): ?string
    {
        if (!empty($this->pacs_preview_url)) {
            return $this->pacs_preview_url;
        }

        if (empty($this->file_path)) {
            return null;
        }

        if ($this->disk === 'public') {
            return Storage::disk('public')->url($this->file_path);
        }

        return route('radiology.files.stream', ['imagingFile' => $this->id], false);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (!empty($this->thumbnail_path)) {
            return Storage::disk($this->disk)->url($this->thumbnail_path);
        }

        return $this->getFileUrlAttribute();
    }
}
