<?php

namespace App\Domain\Administration\Http\Controllers;

use App\Domain\Administration\Models\BackupLog;
use App\Domain\Administration\Services\BackupService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BackupManagementController extends Controller
{
    public function __construct(
        protected BackupService $backupService
    ) {}

    /**
     * List all database backups and disaster recovery readiness scorecard.
     */
    public function index(): JsonResponse
    {
        $data = $this->backupService->getBackups();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Trigger an automated database backup snapshot.
     */
    public function store(Request $request): JsonResponse
    {
        $type = $request->input('backup_type', 'database');
        $notes = $request->input('notes');

        $backup = $this->backupService->createBackup($type, $notes);

        return response()->json([
            'success' => true,
            'message' => 'PostgreSQL automated backup snapshot created successfully.',
            'data' => $backup,
        ], Response::HTTP_CREATED);
    }

    /**
     * Verify cryptographic SHA-256 integrity checksum for a backup.
     */
    public function verify(string $id): JsonResponse
    {
        $backup = BackupLog::findOrFail($id);
        $result = $this->backupService->verifyBackup($backup);

        return response()->json([
            'success' => true,
            'message' => 'Cryptographic SHA-256 integrity verified.',
            'data' => $result,
        ]);
    }

    /**
     * Acceptance Criteria: Backup restore has been tested end-to-end at least once before go-live.
     * Execute disaster recovery restore verification drill.
     */
    public function testRestoreDrill(string $id): JsonResponse
    {
        $backup = BackupLog::findOrFail($id);
        $result = $this->backupService->testRestoreDrill($backup);

        return response()->json([
            'success' => true,
            'message' => 'Disaster recovery restore verification drill completed successfully.',
            'data' => $result,
        ]);
    }
}
