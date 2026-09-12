<?php

namespace App\Domain\SuperAdmin\Http\Controllers;

use App\Domain\SuperAdmin\Models\PlatformAnnouncement;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuperAdminAnnouncementController extends Controller
{
    /**
     * List all platform announcements.
     */
    public function index(): JsonResponse
    {
        $announcements = PlatformAnnouncement::with('creator:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($announcements);
    }

    /**
     * Create a new platform-wide broadcast announcement.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'severity' => 'required|string|in:info,warning,critical,maintenance',
            'target_plans' => 'nullable|array',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
        ]);

        $announcement = PlatformAnnouncement::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'severity' => $validated['severity'],
            'target_plans' => $validated['target_plans'] ?? ['*'],
            'is_active' => $validated['is_active'] ?? true,
            'starts_at' => $validated['starts_at'] ?? now(),
            'expires_at' => $validated['expires_at'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Platform announcement broadcast published.',
            'data' => $announcement,
        ], 201);
    }

    /**
     * Update an announcement.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $announcement = PlatformAnnouncement::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'severity' => 'sometimes|required|string|in:info,warning,critical,maintenance',
            'target_plans' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'expires_at' => 'nullable|date',
        ]);

        $announcement->update($validated);

        return response()->json([
            'message' => 'Announcement updated.',
            'data' => $announcement->fresh(),
        ]);
    }

    /**
     * Remove an announcement.
     */
    public function destroy(string $id): JsonResponse
    {
        $announcement = PlatformAnnouncement::findOrFail($id);
        $announcement->delete();

        return response()->json([
            'message' => 'Announcement removed.',
        ]);
    }

    /**
     * Public/Tenant endpoint: fetch currently active announcements matching tenant plan.
     */
    public function activeAnnouncements(Request $request): JsonResponse
    {
        $user = $request->user();
        $plan = $user?->organization?->plan_tier ?? 'community';

        $announcements = PlatformAnnouncement::where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function ($item) use ($plan) {
                $plans = $item->target_plans ?? ['*'];
                return in_array('*', $plans, true) || in_array($plan, $plans, true);
            })
            ->values();

        return response()->json([
            'data' => $announcements,
        ]);
    }
}
