<?php

namespace App\Domain\Administration\Http\Controllers;

use App\Domain\Administration\Http\Requests\SendNotificationRequest;
use App\Domain\Administration\Http\Requests\StoreNotificationTemplateRequest;
use App\Domain\Administration\Http\Requests\UpdateNotificationTemplateRequest;
use App\Domain\Administration\Models\NotificationLog;
use App\Domain\Administration\Models\NotificationTemplate;
use App\Domain\Administration\Services\Notification\NotificationEngineService;
use App\Domain\Shared\Models\Organization;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationManagementController extends Controller
{
    public function __construct(
        protected NotificationEngineService $notificationEngine
    ) {}

    /**
     * List all notification templates.
     */
    public function getTemplates(Request $request): JsonResponse
    {
        $query = NotificationTemplate::with(['branch', 'organization'])->latest('created_at');

        if ($request->query('channel')) {
            $query->where('channel', $request->query('channel'));
        }

        $templates = $query->get();

        // Prepopulate defaults if empty
        if ($templates->isEmpty()) {
            $org = Organization::first();
            if ($org) {
                $this->notificationEngine->seedDefaultTemplates($org->id);
                $templates = NotificationTemplate::with(['branch', 'organization'])->latest('created_at')->get();
            }
        }

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    /**
     * Create a new notification template.
     */
    public function storeTemplate(StoreNotificationTemplateRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (empty($data['organization_id'])) {
            $org = Organization::first();
            $data['organization_id'] = $org?->id;
        }

        $template = NotificationTemplate::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Notification template created successfully.',
            'data' => $template,
        ], Response::HTTP_CREATED);
    }

    /**
     * Update an existing notification template.
     */
    public function updateTemplate(UpdateNotificationTemplateRequest $request, string $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Notification template updated successfully.',
            'data' => $template->fresh(),
        ]);
    }

    /**
     * Delete a notification template.
     */
    public function destroyTemplate(string $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification template deleted successfully.',
        ]);
    }

    /**
     * Dispatch notification via SMS, Email, or Push.
     */
    public function send(SendNotificationRequest $request): JsonResponse
    {
        $log = $this->notificationEngine->send($request->validated());

        return response()->json([
            'success' => true,
            'message' => ($log->status === 'sent')
                ? 'Notification transmitted successfully.'
                : 'Notification queued for retry delivery (logged with failure details).',
            'data' => $log,
        ], ($log->status === 'sent') ? Response::HTTP_OK : Response::HTTP_ACCEPTED);
    }

    /**
     * Get paginated notification audit trail.
     */
    public function getLogs(Request $request): JsonResponse
    {
        $logs = $this->notificationEngine->getLogs($request->all());

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Retry a single failed or retrying notification.
     */
    public function retry(string $id): JsonResponse
    {
        $log = NotificationLog::findOrFail($id);
        $updated = $this->notificationEngine->retry($log);

        return response()->json([
            'success' => true,
            'message' => ($updated->status === 'sent')
                ? 'Notification successfully recovered and delivered.'
                : 'Retry attempt logged; provider still unavailable.',
            'data' => $updated,
        ]);
    }

    /**
     * Process batch retries for pending failed logs.
     */
    public function retryAll(): JsonResponse
    {
        $summary = $this->notificationEngine->retryPendingNotifications();

        return response()->json([
            'success' => true,
            'message' => 'Batch retry processed.',
            'data' => $summary,
        ]);
    }
}
