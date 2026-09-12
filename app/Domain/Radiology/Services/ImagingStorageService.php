<?php

namespace App\Domain\Radiology\Services;

use App\Domain\Radiology\Models\ImagingFile;
use App\Domain\Radiology\Models\ImagingFileChunk;
use App\Domain\Radiology\Models\ImagingOrder;
use DomainException;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImagingStorageService
{
    /**
     * Store standard direct upload (JPEG, PNG, DICOM).
     */
    public function storeDirectUpload(
        ImagingOrder $order,
        UploadedFile $file,
        ?string $reportId = null,
        ?string $userId = null
    ): ImagingFile {
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension() ?: 'dcm');
        $mimeType = $file->getMimeType() ?: 'application/octet-stream';
        $fileSize = $file->getSize();

        $isDicom = in_array($extension, ['dcm', 'dicom'], true) || str_contains($mimeType, 'dicom');
        $filename = 'IMG-' . strtoupper(Str::random(12)) . '.' . $extension;
        $folder = "radiology/orders/{$order->id}";

        $path = $file->storeAs($folder, $filename, $disk);

        // Mock/generate standard DICOM tags if DICOM
        $sopInstanceUid = $isDicom ? '1.2.826.0.1.3680043.9.' . mt_rand(1000000, 9999999) . '.' . time() : null;

        $pacsViewerUrl = null;
        if ($isDicom) {
            $pacsBase = config('services.pacs.viewer_url', 'https://pacs.metrohealth.org/viewer');
            $pacsViewerUrl = "{$pacsBase}?studyUID=" . urlencode($order->dicom_study_uid ?? '1.2.840.113619.2') . "&sopUID=" . urlencode($sopInstanceUid);
        }

        $imagingFile = ImagingFile::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $order->organization_id,
            'branch_id' => $order->branch_id,
            'imaging_order_id' => $order->id,
            'imaging_report_id' => $reportId,
            'patient_id' => $order->patient_id,
            'file_name' => $filename,
            'original_file_name' => $originalName,
            'file_path' => $path,
            'disk' => $disk,
            'mime_type' => $isDicom ? 'application/dicom' : $mimeType,
            'file_size_bytes' => $fileSize,
            'is_dicom' => $isDicom,
            'dicom_sop_instance_uid' => $sopInstanceUid,
            'series_description' => $order->procedure_name,
            'pacs_preview_url' => $pacsViewerUrl,
            'window_center' => $isDicom ? 40.0 : null,
            'window_width' => $isDicom ? 400.0 : null,
            'upload_status' => 'completed',
            'uploaded_by' => $userId,
        ]);

        return $imagingFile;
    }

    /**
     * Store single chunk during large file chunked upload.
     */
    public function storeChunk(string $uploadId, int $chunkIndex, int $totalChunks, UploadedFile $chunkFile): ImagingFileChunk
    {
        $disk = 'local';
        $folder = "chunks/{$uploadId}";
        $chunkName = "chunk_{$chunkIndex}.part";

        $path = $chunkFile->storeAs($folder, $chunkName, $disk);

        return ImagingFileChunk::updateOrCreate(
            [
                'upload_id' => $uploadId,
                'chunk_index' => $chunkIndex,
            ],
            [
                'id' => (string) Str::uuid(),
                'total_chunks' => $totalChunks,
                'chunk_file_path' => $path,
                'chunk_size_bytes' => $chunkFile->getSize(),
                'is_assembled' => false,
            ]
        );
    }

    /**
     * Assemble all received chunks into the final large image file.
     */
    public function assembleChunks(
        string $uploadId,
        ImagingOrder $order,
        string $originalFilename,
        ?string $reportId = null,
        ?string $userId = null
    ): ImagingFile {
        $chunks = ImagingFileChunk::where('upload_id', $uploadId)
            ->orderBy('chunk_index', 'asc')
            ->get();

        if ($chunks->isEmpty()) {
            throw new DomainException("No chunks found for upload ID '{$uploadId}'.");
        }

        $totalExpected = $chunks->first()->total_chunks;
        if ($chunks->count() < $totalExpected) {
            throw new DomainException("Upload incomplete: received {$chunks->count()} of {$totalExpected} chunks.");
        }

        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION) ?: 'dcm');
        $filename = 'IMG-CHUNKED-' . strtoupper(Str::random(10)) . '.' . $extension;
        $folder = "radiology/orders/{$order->id}";
        $finalRelativePath = "{$folder}/{$filename}";

        $tempFinalPath = tempnam(sys_get_temp_dir(), 'ris_chunk_');
        $outHandle = fopen($tempFinalPath, 'wb');

        if (!$outHandle) {
            throw new Exception("Unable to open temp file for chunk assembly.");
        }

        $totalBytes = 0;
        foreach ($chunks as $chunk) {
            if (!Storage::disk('local')->exists($chunk->chunk_file_path)) {
                fclose($outHandle);
                @unlink($tempFinalPath);
                throw new DomainException("Missing chunk file at index {$chunk->chunk_index}.");
            }

            $inHandle = Storage::disk('local')->readStream($chunk->chunk_file_path);
            while (!feof($inHandle)) {
                $buf = fread($inHandle, 1048576); // 1MB buffer
                fwrite($outHandle, $buf);
                $totalBytes += strlen($buf);
            }
            fclose($inHandle);
        }
        fclose($outHandle);

        // Put assembled file into target storage disk
        $stream = fopen($tempFinalPath, 'r');
        Storage::disk($disk)->put($finalRelativePath, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        // Clean up temporary files & chunk records
        @unlink($tempFinalPath);
        Storage::disk('local')->deleteDirectory("chunks/{$uploadId}");
        ImagingFileChunk::where('upload_id', $uploadId)->update(['is_assembled' => true]);

        $isDicom = in_array($extension, ['dcm', 'dicom'], true);
        $sopInstanceUid = $isDicom ? '1.2.826.0.1.3680043.9.' . mt_rand(1000000, 9999999) . '.' . time() : null;

        $pacsViewerUrl = null;
        if ($isDicom) {
            $pacsBase = config('services.pacs.viewer_url', 'https://pacs.metrohealth.org/viewer');
            $pacsViewerUrl = "{$pacsBase}?studyUID=" . urlencode($order->dicom_study_uid ?? '1.2.840.113619.2') . "&sopUID=" . urlencode($sopInstanceUid);
        }

        return ImagingFile::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $order->organization_id,
            'branch_id' => $order->branch_id,
            'imaging_order_id' => $order->id,
            'imaging_report_id' => $reportId,
            'patient_id' => $order->patient_id,
            'file_name' => $filename,
            'original_file_name' => $originalFilename,
            'file_path' => $finalRelativePath,
            'disk' => $disk,
            'mime_type' => $isDicom ? 'application/dicom' : 'image/jpeg',
            'file_size_bytes' => $totalBytes,
            'is_dicom' => $isDicom,
            'dicom_sop_instance_uid' => $sopInstanceUid,
            'series_description' => $order->procedure_name,
            'pacs_preview_url' => $pacsViewerUrl,
            'window_center' => $isDicom ? 40.0 : null,
            'window_width' => $isDicom ? 400.0 : null,
            'upload_status' => 'completed',
            'uploaded_by' => $userId,
        ]);
    }
}
