<?php

namespace App\Domain\SuperAdmin\Http\Controllers;

use App\Domain\Shared\Models\Organization;
use App\Domain\SuperAdmin\Models\Subscription;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuperAdminSubscriptionController extends Controller
{
    /**
     * List all subscriptions with hospital organization details.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Subscription::with('organization:id,name,code,is_active');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('plan_tier')) {
            $query->where('plan_tier', $request->input('plan_tier'));
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->paginate(25);

        return response()->json($subscriptions);
    }

    /**
     * Update subscription plan or status.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $subscription = Subscription::findOrFail($id);

        $validated = $request->validate([
            'plan_tier' => 'sometimes|required|string|in:community,regional,enterprise',
            'status' => 'sometimes|required|string|in:active,trialing,past_due,suspended,cancelled',
            'billing_cycle' => 'sometimes|required|string|in:monthly,annual',
            'amount_cents' => 'nullable|integer|min:0',
        ]);

        $subscription->update($validated);

        if (isset($validated['plan_tier'])) {
            Organization::where('id', $subscription->organization_id)->update([
                'plan_tier' => $validated['plan_tier'],
            ]);
        }

        if (isset($validated['status'])) {
            Organization::where('id', $subscription->organization_id)->update([
                'subscription_status' => $validated['status'],
            ]);
        }

        return response()->json([
            'message' => 'Subscription updated successfully.',
            'data' => $subscription->fresh()->load('organization'),
        ]);
    }
}
