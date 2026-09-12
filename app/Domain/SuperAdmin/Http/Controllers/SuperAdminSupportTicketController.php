<?php

namespace App\Domain\SuperAdmin\Http\Controllers;

use App\Domain\SuperAdmin\Models\SupportTicket;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SuperAdminSupportTicketController extends Controller
{
    /**
     * List support tickets with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SupportTicket::with(['organization:id,name,code', 'assignee:id,name,email']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($tickets);
    }

    /**
     * Create a support ticket.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => 'required|uuid|exists:organizations,id',
            'reporter_name' => 'required|string|max:150',
            'reporter_email' => 'required|email|max:150',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'nullable|string|in:billing,clinical,integration,bug,feature_request,system',
            'priority' => 'nullable|string|in:low,medium,high,critical',
        ]);

        $ticket = SupportTicket::create([
            'ticket_number' => 'TICK-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
            'organization_id' => $validated['organization_id'],
            'reporter_user_id' => $request->user()?->id,
            'reporter_name' => $validated['reporter_name'],
            'reporter_email' => $validated['reporter_email'],
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'category' => $validated['category'] ?? 'system',
            'priority' => $validated['priority'] ?? 'medium',
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'Support ticket logged successfully.',
            'data' => $ticket->load('organization'),
        ], 201);
    }

    /**
     * Update ticket status or resolution.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $ticket = SupportTicket::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|required|string|in:open,in_progress,waiting_on_client,resolved,closed',
            'priority' => 'sometimes|required|string|in:low,medium,high,critical',
            'assigned_to' => 'nullable|uuid|exists:users,id',
            'resolution_notes' => 'nullable|string',
        ]);

        if (isset($validated['status']) && in_array($validated['status'], ['resolved', 'closed'], true) && !$ticket->resolved_at) {
            $validated['resolved_at'] = now();
        }

        $ticket->update($validated);

        return response()->json([
            'message' => 'Support ticket updated.',
            'data' => $ticket->fresh(['organization', 'assignee']),
        ]);
    }
}
