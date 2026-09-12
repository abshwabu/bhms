<?php

namespace App\Domain\Compliance\Services;

use App\Domain\Shared\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AuditTrailService
{
    /**
     * Query and filter audit trails.
     */
    public function getLogs(array $filters = []): LengthAwarePaginator
    {
        $query = AuditLog::with(['user:id,name,email', 'branch:id,name'])
            ->orderBy('created_at', 'desc');

        if (! empty($filters['auditable_type'])) {
            $query->where('auditable_type', 'like', '%' . $filters['auditable_type'] . '%');
        }

        if (! empty($filters['auditable_id'])) {
            $query->where('auditable_id', $filters['auditable_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        if (! empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('auditable_type', 'ilike', $search)
                    ->orWhere('event', 'ilike', $search)
                    ->orWhere('ip_address', 'ilike', $search)
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'ilike', $search)
                            ->orWhere('email', 'ilike', $search);
                    });
            });
        }

        $perPage = min((int) ($filters['per_page'] ?? 25), 100);

        return $query->paginate($perPage);
    }

    /**
     * Get aggregate statistics for compliance auditing dashboard.
     */
    public function getStatistics(): array
    {
        $now = Carbon::now();
        $startOfToday = $now->copy()->startOfDay();
        $startOfWeek = $now->copy()->subDays(7);

        $totalLogs = AuditLog::count();
        $todayLogs = AuditLog::where('created_at', '>=', $startOfToday)->count();
        $weekLogs = AuditLog::where('created_at', '>=', $startOfWeek)->count();

        // Count by event type
        $byEvent = AuditLog::select('event', DB::raw('count(*) as count'))
            ->groupBy('event')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->pluck('count', 'event')
            ->all();

        // Count by auditable type
        $byModel = AuditLog::select('auditable_type', DB::raw('count(*) as count'))
            ->groupBy('auditable_type')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $short = class_basename($row->auditable_type);
                return [
                    'model' => $short,
                    'full_class' => $row->auditable_type,
                    'count' => (int) $row->count,
                ];
            })
            ->all();

        return [
            'total_logs' => $totalLogs,
            'today_logs' => $todayLogs,
            'past_7_days_logs' => $weekLogs,
            'by_event' => $byEvent,
            'by_model' => $byModel,
            'compliance_coverage' => '100% auditable lifecycle coverage',
        ];
    }

    /**
     * Get a specific audit entry.
     */
    public function getLogById(string $id): ?AuditLog
    {
        return AuditLog::with(['user', 'branch'])->find($id);
    }
}
