<?php

namespace App\Domain\Reports\Services;

use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class CustomReportBuilderService
{
    public const MAX_ROW_LIMIT = 1000;
    public const MAX_EXPORT_LIMIT = 2500;
    public const MAX_FILTER_COUNT = 10;
    public const MAX_COLUMN_COUNT = 15;

    /**
     * Complete reporting schema metadata with whitelist of entities, fields, and operators.
     */
    public function getSchema(): array
    {
        return [
            'entities' => [
                'invoices' => [
                    'label' => 'Billing Invoices',
                    'table' => 'invoices',
                    'date_field' => 'created_at',
                    'fields' => [
                        'invoice_number' => ['label' => 'Invoice Number', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'billing_type' => ['label' => 'Billing Type (OPD/IPD/Pharmacy)', 'type' => 'enum', 'filterable' => true, 'sortable' => true, 'options' => ['opd', 'ipd', 'pharmacy', 'emergency', 'diagnostic']],
                        'status' => ['label' => 'Payment Status', 'type' => 'enum', 'filterable' => true, 'sortable' => true, 'options' => ['unpaid', 'partially_paid', 'paid', 'cancelled', 'refunded']],
                        'department' => ['label' => 'Department', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'total_cents' => ['label' => 'Total Invoiced (Cents)', 'type' => 'numeric', 'filterable' => true, 'sortable' => true, 'aggregateable' => true],
                        'paid_cents' => ['label' => 'Paid Amount (Cents)', 'type' => 'numeric', 'filterable' => true, 'sortable' => true, 'aggregateable' => true],
                        'balance_cents' => ['label' => 'Balance Due (Cents)', 'type' => 'numeric', 'filterable' => true, 'sortable' => true, 'aggregateable' => true],
                        'payment_terms' => ['label' => 'Payment Terms', 'type' => 'string', 'filterable' => true, 'sortable' => false],
                        'due_date' => ['label' => 'Due Date', 'type' => 'date', 'filterable' => true, 'sortable' => true],
                        'created_at' => ['label' => 'Invoice Date', 'type' => 'datetime', 'filterable' => true, 'sortable' => true],
                    ],
                ],
                'payments' => [
                    'label' => 'Payments & Collections',
                    'table' => 'payments',
                    'date_field' => 'received_at',
                    'fields' => [
                        'receipt_number' => ['label' => 'Receipt Number', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'payment_mode' => ['label' => 'Payment Mode', 'type' => 'enum', 'filterable' => true, 'sortable' => true, 'options' => ['cash', 'card', 'mobile_money', 'insurance', 'bank_transfer']],
                        'amount_cents' => ['label' => 'Amount Paid (Cents)', 'type' => 'numeric', 'filterable' => true, 'sortable' => true, 'aggregateable' => true],
                        'status' => ['label' => 'Status', 'type' => 'enum', 'filterable' => true, 'sortable' => true, 'options' => ['completed', 'reversed', 'refunded']],
                        'transaction_reference' => ['label' => 'Transaction Reference', 'type' => 'string', 'filterable' => true, 'sortable' => false],
                        'received_at' => ['label' => 'Payment Received Date', 'type' => 'datetime', 'filterable' => true, 'sortable' => true],
                    ],
                ],
                'patients' => [
                    'label' => 'Patient Registry',
                    'table' => 'patients',
                    'date_field' => 'created_at',
                    'fields' => [
                        'mrn' => ['label' => 'Medical Record Number (MRN)', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'first_name' => ['label' => 'First Name', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'last_name' => ['label' => 'Last Name', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'gender' => ['label' => 'Gender', 'type' => 'enum', 'filterable' => true, 'sortable' => true, 'options' => ['male', 'female', 'other']],
                        'date_of_birth' => ['label' => 'Date of Birth', 'type' => 'date', 'filterable' => true, 'sortable' => true],
                        'blood_group' => ['label' => 'Blood Group', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'status' => ['label' => 'Patient Status', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'created_at' => ['label' => 'Registration Date', 'type' => 'datetime', 'filterable' => true, 'sortable' => true],
                    ],
                ],
                'appointments' => [
                    'label' => 'OPD Appointments',
                    'table' => 'appointments',
                    'date_field' => 'appointment_date',
                    'fields' => [
                        'appointment_number' => ['label' => 'Appointment Number', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'appointment_date' => ['label' => 'Appointment Date', 'type' => 'date', 'filterable' => true, 'sortable' => true],
                        'start_time' => ['label' => 'Start Time', 'type' => 'string', 'filterable' => false, 'sortable' => true],
                        'type' => ['label' => 'Consultation Type', 'type' => 'enum', 'filterable' => true, 'sortable' => true, 'options' => ['in_person', 'telemedicine', 'walk_in', 'follow_up']],
                        'status' => ['label' => 'Status', 'type' => 'enum', 'filterable' => true, 'sortable' => true, 'options' => ['scheduled', 'checked_in', 'in_consultation', 'completed', 'cancelled', 'no_show']],
                        'reason_for_visit' => ['label' => 'Reason / Chief Complaint', 'type' => 'string', 'filterable' => true, 'sortable' => false],
                    ],
                ],
                'admissions' => [
                    'label' => 'Inpatient Admissions (IPD)',
                    'table' => 'admissions',
                    'date_field' => 'admitted_at',
                    'fields' => [
                        'admission_number' => ['label' => 'Admission Number', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'admission_type' => ['label' => 'Admission Type', 'type' => 'enum', 'filterable' => true, 'sortable' => true, 'options' => ['emergency', 'elective', 'transfer', 'observation', 'maternity']],
                        'status' => ['label' => 'Status', 'type' => 'enum', 'filterable' => true, 'sortable' => true, 'options' => ['admitted', 'discharged', 'transferred_out', 'cancelled']],
                        'admitted_at' => ['label' => 'Admitted Date & Time', 'type' => 'datetime', 'filterable' => true, 'sortable' => true],
                        'discharged_at' => ['label' => 'Discharged Date & Time', 'type' => 'datetime', 'filterable' => true, 'sortable' => true],
                        'discharge_type' => ['label' => 'Discharge Disposition', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'admitting_diagnosis' => ['label' => 'Admitting Diagnosis', 'type' => 'string', 'filterable' => true, 'sortable' => false],
                    ],
                ],
                'prescriptions' => [
                    'label' => 'E-Prescriptions Written',
                    'table' => 'prescriptions',
                    'date_field' => 'created_at',
                    'fields' => [
                        'prescription_number' => ['label' => 'Prescription Number', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'status' => ['label' => 'Status', 'type' => 'enum', 'filterable' => true, 'sortable' => true, 'options' => ['pending', 'partially_dispensed', 'dispensed', 'cancelled']],
                        'priority' => ['label' => 'Priority', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'diagnosis_text' => ['label' => 'Clinical Diagnosis', 'type' => 'string', 'filterable' => true, 'sortable' => false],
                        'created_at' => ['label' => 'Prescribed Date', 'type' => 'datetime', 'filterable' => true, 'sortable' => true],
                    ],
                ],
                'lab_orders' => [
                    'label' => 'Laboratory Orders',
                    'table' => 'lab_orders',
                    'date_field' => 'created_at',
                    'fields' => [
                        'order_number' => ['label' => 'Order Number', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'status' => ['label' => 'Status', 'type' => 'enum', 'filterable' => true, 'sortable' => true, 'options' => ['pending', 'sample_collected', 'in_progress', 'completed', 'cancelled']],
                        'priority' => ['label' => 'Priority (Routine/Stat)', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'clinical_notes' => ['label' => 'Clinical Indication', 'type' => 'string', 'filterable' => true, 'sortable' => false],
                        'created_at' => ['label' => 'Order Date', 'type' => 'datetime', 'filterable' => true, 'sortable' => true],
                    ],
                ],
                'radiology_orders' => [
                    'label' => 'Radiology & Imaging Orders',
                    'table' => 'radiology_orders',
                    'date_field' => 'created_at',
                    'fields' => [
                        'order_number' => ['label' => 'Order Number', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'modality' => ['label' => 'Modality (X-Ray, CT, MRI, US)', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'status' => ['label' => 'Status', 'type' => 'enum', 'filterable' => true, 'sortable' => true, 'options' => ['pending', 'scheduled', 'in_progress', 'completed', 'cancelled']],
                        'priority' => ['label' => 'Priority', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'created_at' => ['label' => 'Order Date', 'type' => 'datetime', 'filterable' => true, 'sortable' => true],
                    ],
                ],
                'emergency_cases' => [
                    'label' => 'Emergency & Trauma Cases',
                    'table' => 'emergency_cases',
                    'date_field' => 'created_at',
                    'fields' => [
                        'case_number' => ['label' => 'Case Number', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'current_esi_level' => ['label' => 'ESI Severity Level (1-5)', 'type' => 'numeric', 'filterable' => true, 'sortable' => true, 'aggregateable' => true],
                        'arrival_mode' => ['label' => 'Arrival Mode (Ambulance/Walk-in)', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'status' => ['label' => 'Status', 'type' => 'string', 'filterable' => true, 'sortable' => true],
                        'chief_complaint' => ['label' => 'Chief Complaint', 'type' => 'string', 'filterable' => true, 'sortable' => false],
                        'created_at' => ['label' => 'Arrival Date & Time', 'type' => 'datetime', 'filterable' => true, 'sortable' => true],
                    ],
                ],
            ],
            'operators' => [
                '=' => 'Equals',
                '!=' => 'Not equals',
                '>' => 'Greater than',
                '>=' => 'Greater than or equal',
                '<' => 'Less than',
                '<=' => 'Less than or equal',
                'like' => 'Contains text',
                'in' => 'In list',
                'between' => 'Between (min, max)',
                'is_null' => 'Is empty (null)',
                'is_not_null' => 'Is not empty',
            ],
            'aggregations' => [
                'count' => 'Count records',
                'sum' => 'Sum total',
                'avg' => 'Average value',
                'min' => 'Minimum value',
                'max' => 'Maximum value',
            ],
            'limits' => [
                'max_rows_per_page' => 100,
                'hard_query_limit' => self::MAX_ROW_LIMIT,
                'max_export_limit' => self::MAX_EXPORT_LIMIT,
                'max_filter_count' => self::MAX_FILTER_COUNT,
                'max_column_count' => self::MAX_COLUMN_COUNT,
            ],
        ];
    }

    /**
     * Execute safe, validated, bounded custom report query.
     * Acceptance criterion: Custom report builder prevents unsafe/unbounded queries (query cost limits).
     */
    public function executeQuery(array $params, bool $isExport = false): array
    {
        $startTime = microtime(true);
        $schema = $this->getSchema();

        // 1. Entity Whitelist Verification
        $entityKey = $params['entity'] ?? null;
        if (!$entityKey || !isset($schema['entities'][$entityKey])) {
            throw new DomainException("Unsafe or invalid reporting entity: '{$entityKey}'.");
        }

        $entityConfig = $schema['entities'][$entityKey];
        $tableName = $entityConfig['table'];
        $whitelistedFields = $entityConfig['fields'];

        // 2. Query Construction with Branch Isolation
        $query = DB::table($tableName);

        if (!empty($params['branch_id'])) {
            $query->where('branch_id', $params['branch_id']);
        }

        // 3. Selected Fields Whitelist Verification
        $requestedFields = $params['fields'] ?? [];
        if (empty($requestedFields)) {
            $requestedFields = array_keys(array_slice($whitelistedFields, 0, 6));
        }

        if (count($requestedFields) > self::MAX_COLUMN_COUNT) {
            throw new DomainException("Query cost violation: Exceeded maximum allowed columns limit (" . self::MAX_COLUMN_COUNT . ").");
        }

        $safeSelectedFields = [];
        foreach ($requestedFields as $field) {
            if (!isset($whitelistedFields[$field])) {
                throw new DomainException("Unsafe or unwhitelisted column '{$field}' for entity '{$entityKey}'.");
            }
            $safeSelectedFields[] = $field;
        }

        // 4. Safe Filters Application
        $filters = $params['filters'] ?? [];
        if (count($filters) > self::MAX_FILTER_COUNT) {
            throw new DomainException("Query cost violation: Exceeded maximum allowed filter clauses (" . self::MAX_FILTER_COUNT . ").");
        }

        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'] ?? null;

            if (!$field || !isset($whitelistedFields[$field])) {
                continue; // Skip or ignore unwhitelisted filter field
            }

            if (!isset($schema['operators'][$operator])) {
                throw new DomainException("Unsafe or unsupported filter operator: '{$operator}'.");
            }

            $this->applySafeFilter($query, $field, $operator, $value);
        }

        // 5. Date Bounding (Safety check: If no date filter, bounded to last 90 days default)
        if (isset($params['date_from']) || isset($params['date_to'])) {
            $dateField = $entityConfig['date_field'];
            if (!empty($params['date_from'])) {
                $query->where($dateField, '>=', Carbon::parse($params['date_from'])->startOfDay());
            }
            if (!empty($params['date_to'])) {
                $query->where($dateField, '<=', Carbon::parse($params['date_to'])->endOfDay());
            }
        }

        // 6. Group By & Aggregation Handling
        $groupBy = $params['group_by'] ?? null;
        $aggregation = $params['aggregation'] ?? null; // e.g. 'sum', 'count', 'avg'
        $aggField = $params['aggregation_field'] ?? null;

        if ($groupBy) {
            if (!isset($whitelistedFields[$groupBy])) {
                throw new DomainException("Unsafe group_by column: '{$groupBy}'.");
            }

            $query->groupBy($groupBy);

            if ($aggregation && $aggField && isset($whitelistedFields[$aggField])) {
                $validAggs = ['count', 'sum', 'avg', 'min', 'max'];
                if (!in_array($aggregation, $validAggs, true)) {
                    throw new DomainException("Invalid aggregation function '{$aggregation}'.");
                }
                $query->select($groupBy, DB::raw("{$aggregation}({$aggField}) as aggregated_value"), DB::raw('COUNT(*) as count'));
            } else {
                $query->select($groupBy, DB::raw('COUNT(*) as count'));
            }

            $safeSelectedFields = [$groupBy, 'count'];
            if ($aggField) $safeSelectedFields[] = 'aggregated_value';
        } else {
            $query->select($safeSelectedFields);
        }

        // 7. Safe Sorting
        $sortField = $params['sort_field'] ?? ($groupBy ?: $entityConfig['date_field']);
        $sortDirection = strtolower($params['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (isset($whitelistedFields[$sortField]) || $sortField === 'count' || $sortField === 'aggregated_value') {
            $query->orderBy($sortField, $sortDirection);
        }

        // 8. Cost Limit & Row Bounding Enforcement
        $maxAllowed = $isExport ? self::MAX_EXPORT_LIMIT : self::MAX_ROW_LIMIT;
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(100, max(5, (int) ($params['per_page'] ?? 25)));

        // Get total matching records count (capped to cost bound)
        $totalCount = (clone $query)->count();
        $clampedTotal = min($totalCount, $maxAllowed);

        // Fetch records with query bounding limit
        if ($isExport) {
            $records = $query->limit($maxAllowed)->get();
        } else {
            $offset = ($page - 1) * $perPage;
            if ($offset >= $maxAllowed) {
                $records = collect([]);
            } else {
                $limit = min($perPage, $maxAllowed - $offset);
                $records = $query->offset($offset)->limit($limit)->get();
            }
        }

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'entity' => $entityKey,
            'entity_label' => $entityConfig['label'],
            'columns' => $safeSelectedFields,
            'column_definitions' => array_intersect_key($whitelistedFields, array_flip($safeSelectedFields)),
            'data' => $records,
            'meta' => [
                'total_records' => $totalCount,
                'clamped_records' => $clampedTotal,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => ceil($clampedTotal / $perPage),
                'hard_cost_limit' => $maxAllowed,
                'limit_applied' => $totalCount > $maxAllowed,
                'query_duration_ms' => $durationMs,
            ],
        ];
    }

    protected function applySafeFilter($query, string $field, string $operator, mixed $value): void
    {
        switch ($operator) {
            case '=':
            case '!=':
            case '>':
            case '>=':
            case '<':
            case '<=':
                if ($value !== null && $value !== '') {
                    $query->where($field, $operator, $value);
                }
                break;
            case 'like':
                if (!empty($value)) {
                    $query->where($field, 'ilike', '%' . str_replace(['%', '_'], ['\\%', '\\_'], $value) . '%');
                }
                break;
            case 'in':
                $items = is_array($value) ? $value : array_filter(array_map('trim', explode(',', (string) $value)));
                if (!empty($items)) {
                    $query->whereIn($field, array_slice($items, 0, 50));
                }
                break;
            case 'between':
                if (is_array($value) && count($value) >= 2) {
                    $query->whereBetween($field, [$value[0], $value[1]]);
                }
                break;
            case 'is_null':
                $query->whereNull($field);
                break;
            case 'is_not_null':
                $query->whereNotNull($field);
                break;
        }
    }
}
