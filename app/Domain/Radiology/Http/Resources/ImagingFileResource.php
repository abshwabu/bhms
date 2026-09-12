<?php

namespace App\Domain\Radiology\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImagingFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'imaging_order_id' => $this->imaging_order_id,
            'imaging_report_id' => $this->imaging_report_id,
            'patient_id' => $this->patient_id,
            'file_name' => $this->file_name,
            'original_file_name' => $this->original_file_name,
            'mime_type' => $this->mime_type,
            'file_size_bytes' => $this->file_size_bytes,
            'file_size_formatted' => $this->formatBytes($this->file_size_bytes),
            'is_dicom' => $this->is_dicom,
            'dicom_sop_instance_uid' => $this->dicom_sop_instance_uid,
            'series_description' => $this->series_description,
            'series_number' => $this->series_number,
            'instance_number' => $this->instance_number,
            'window_center' => $this->window_center,
            'window_width' => $this->window_width,
            'file_url' => $this->file_url,
            'thumbnail_url' => $this->thumbnail_url,
            'pacs_wado_url' => $this->pacs_wado_url,
            'pacs_preview_url' => $this->pacs_preview_url,
            'metadata' => $this->metadata,
            'upload_status' => $this->upload_status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
