<?php

namespace App\Domain\Radiology\Http\Controllers;

use App\Domain\Radiology\Http\Requests\InitChunkedUploadRequest;
use App\Domain\Radiology\Http\Requests\UploadChunkRequest;
use App\Domain\Radiology\Http\Resources\ImagingFileResource;
use App\Domain\Radiology\Models\ImagingFile;
use App\Domain\Radiology\Models\ImagingFileChunk;
use App\Domain\Radiology\Models\ImagingOrder;
use App\Domain\Radiology\Services\ImagingStorageService;
use App\Domain\Shared\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use DomainException;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImagingFileController extends Controller
{
    public function __construct(
        protected ImagingStorageService $storageService
    ) {
    }

    /**
     * List imaging files attached to an order or report.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ImagingFile::query()->orderBy('created_at', 'desc');

        if ($request->filled('imaging_order_id')) {
            $query->where('imaging_order_id', $request->input('imaging_order_id'));
        }

        if ($request->filled('imaging_report_id')) {
            $query->where('imaging_report_id', $request->input('imaging_report_id'));
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        $files = $query->get();

        return ApiResponse::success(
            ImagingFileResource::collection($files),
            'Imaging files retrieved.'
        );
    }

    /**
     * Direct file upload (standard JPEGs, PNGs, and DICOMs up to configured upload limit).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'imaging_order_id' => ['required', 'uuid', 'exists:imaging_orders,id'],
            'imaging_report_id' => ['nullable', 'uuid', 'exists:imaging_reports,id'],
            'file' => ['required_without:files', 'nullable', 'file'],
            'files' => ['required_without:file', 'nullable', 'array'],
            'files.*' => ['file'],
        ]);

        $order = ImagingOrder::findOrFail($request->input('imaging_order_id'));
        $reportId = $request->input('imaging_report_id');
        $userId = $request->user()?->id;

        $uploadedFiles = [];

        if ($request->hasFile('file')) {
            $uploadedFiles[] = $this->storageService->storeDirectUpload(
                $order,
                $request->file('file'),
                $reportId,
                $userId
            );
        } elseif ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $uploadedFiles[] = $this->storageService->storeDirectUpload(
                    $order,
                    $file,
                    $reportId,
                    $userId
                );
            }
        }

        return ApiResponse::success(
            ImagingFileResource::collection(collect($uploadedFiles)),
            count($uploadedFiles) . ' imaging file(s) attached successfully.',
            201
        );
    }

    /**
     * Initialize resilient chunked upload session for large multi-hundred MB DICOM or JPEG files.
     */
    public function initChunkedUpload(InitChunkedUploadRequest $request): JsonResponse
    {
        $uploadId = 'UPL-' . date('Ymd') . '-' . Str::random(24);

        return ApiResponse::success([
            'upload_id' => $uploadId,
            'file_name' => $request->input('file_name'),
            'total_chunks' => $request->input('total_chunks'),
            'total_size_bytes' => $request->input('total_size_bytes'),
            'recommended_chunk_size' => 5 * 1024 * 1024, // 5MB chunks recommended
        ], 'Chunked upload session initialized.', 201);
    }

    /**
     * Receive and store an individual file chunk without server timeouts.
     */
    public function uploadChunk(UploadChunkRequest $request): JsonResponse
    {
        $uploadId = $request->input('upload_id');
        $chunkIndex = (int) $request->input('chunk_index');
        $totalChunks = (int) $request->input('total_chunks');

        $chunkRecord = $this->storageService->storeChunk(
            $uploadId,
            $chunkIndex,
            $totalChunks,
            $request->file('chunk')
        );

        $receivedCount = ImagingFileChunk::where('upload_id', $uploadId)->count();
        $progressPct = round(($receivedCount / $totalChunks) * 100, 1);

        return ApiResponse::success([
            'upload_id' => $uploadId,
            'chunk_index' => $chunkIndex,
            'received_chunks' => $receivedCount,
            'total_chunks' => $totalChunks,
            'progress_percentage' => $progressPct,
        ], "Chunk {$chunkIndex}/{$totalChunks} received.");
    }

    /**
     * Finalize and assemble all received chunks into the final stored imaging file.
     */
    public function finalizeChunkedUpload(Request $request): JsonResponse
    {
        $request->validate([
            'upload_id' => ['required', 'string'],
            'imaging_order_id' => ['required', 'uuid', 'exists:imaging_orders,id'],
            'file_name' => ['required', 'string', 'max:255'],
            'imaging_report_id' => ['nullable', 'uuid', 'exists:imaging_reports,id'],
        ]);

        try {
            $order = ImagingOrder::findOrFail($request->input('imaging_order_id'));
            $reportId = $request->input('imaging_report_id');
            $userId = $request->user()?->id;

            $assembledFile = $this->storageService->assembleChunks(
                $request->input('upload_id'),
                $order,
                $request->input('file_name'),
                $reportId,
                $userId
            );

            return ApiResponse::success(
                new ImagingFileResource($assembledFile),
                "Large imaging file {$assembledFile->original_file_name} assembled and stored successfully without timeout.",
                201
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 'CHUNK_ASSEMBLY_ERROR', [], 422);
        } catch (Exception $e) {
            return ApiResponse::error('Failed to assemble chunked file: ' . $e->getMessage(), 'SERVER_ERROR', [], 500);
        }
    }

    /**
     * Stream image file inline for in-browser viewing.
     */
    public function stream(ImagingFile $imagingFile): mixed
    {
        $disk = Storage::disk($imagingFile->disk);

        if (!$disk->exists($imagingFile->file_path)) {
            abort(404, 'Imaging file not found on storage.');
        }

        $fullPath = $disk->path($imagingFile->file_path);

        return response()->file($fullPath, [
            'Content-Type' => $imagingFile->mime_type,
            'Content-Disposition' => 'inline; filename="' . $imagingFile->original_file_name . '"',
        ]);
    }

    /**
     * Download imaging file attachment.
     */
    public function download(ImagingFile $imagingFile): mixed
    {
        $disk = Storage::disk($imagingFile->disk);

        if (!$disk->exists($imagingFile->file_path)) {
            abort(404, 'Imaging file not found on storage.');
        }

        return $disk->download($imagingFile->file_path, $imagingFile->original_file_name);
    }
}
