<?php

namespace App\Domain\SuperAdmin\Http\Controllers;

use App\Domain\Shared\Models\AuditLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuperAdminAuditLogController extends Controller
{
    /**
     * Cross-tenant global audit log ledger for vendor compliance oversight.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with(['organization:id,name,code', 'user:id,name,email']);

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        if ($request->filled('search')) {
            $s = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($s) {
                $q->where('auditable_type', 'ilike', $s)
                    ->orWhere('ip_address', 'ilike', $s)
                    ->orWhere('event', 'ilike', $s);
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(25);

        return response()->json($logs);
    }
}
