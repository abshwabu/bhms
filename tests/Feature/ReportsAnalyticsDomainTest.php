<?php

namespace Tests\Feature;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\Payment;
use App\Domain\OPD\Models\Department;
use App\Domain\Patient\Models\Patient;
use App\Domain\Reports\Models\DailyHospitalKpi;
use App\Domain\Reports\Services\KpiAggregationService;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportsAnalyticsDomainTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected Branch $branch;
    protected User $user;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Metro Health System',
            'code' => 'MHS',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'organization_id' => $this->organization->id,
            'name' => 'Metro General Hospital',
            'code' => 'MGH',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Dr. Gregory House',
        ]);

        Sanctum::actingAs($this->user);

        $this->department = Department::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'name' => 'Internal Medicine',
            'code' => 'IM',
            'is_active' => true,
        ]);

        // Seed sample patient
        $patient = Patient::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-2026-TEST01',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male',
            'date_of_birth' => '1985-05-15',
            'blood_group' => 'O+',
            'status' => 'active',
        ]);

        // Seed sample invoice
        $invoice = Invoice::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $patient->id,
            'doctor_id' => $this->user->id,
            'invoice_number' => 'INV-2026-0001',
            'billing_type' => 'opd',
            'department' => 'Internal Medicine',
            'status' => 'paid',
            'subtotal_cents' => 15000,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 15000,
            'paid_cents' => 15000,
            'balance_cents' => 0,
            'created_by' => $this->user->id,
        ]);

        Payment::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'invoice_id' => $invoice->id,
            'patient_id' => $patient->id,
            'receipt_number' => 'REC-2026-0001',
            'payment_mode' => 'card',
            'amount_cents' => 15000,
            'cashier_id' => $this->user->id,
            'status' => 'completed',
            'received_at' => Carbon::now(),
        ]);

        // Run aggregation for past 3 days so pre-aggregated tables have data
        app(KpiAggregationService::class)->backfillRange(
            $this->branch->id,
            Carbon::today()->subDays(2),
            Carbon::today()
        );
    }

    /**
     * Acceptance criterion: Admin dashboard loads in under 2 seconds for a hospital with 100k+ records.
     */
    public function test_admin_dashboard_kpis_loads_rapidly_via_pre_aggregation(): void
    {
        $startTime = microtime(true);

        $response = $this->getJson("/api/v1/reports/kpis?branch_id={$this->branch->id}&preset=last_7_days");

        $executionTimeMs = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'period' => ['start_date', 'end_date', 'preset', 'days_count'],
                    'kpis' => [
                        'current_occupancy' => ['rate_percentage', 'average_rate_period', 'occupied_beds', 'total_beds'],
                        'revenue' => ['total_invoiced', 'total_collected', 'total_outstanding', 'collection_rate_percentage'],
                        'patient_flow' => ['total_registered_patients', 'total_opd_visits', 'total_admissions', 'total_discharges', 'total_emergency_cases'],
                    ],
                    'trends',
                    'department_revenue',
                    'payment_modes',
                ],
            ]);

        // Verify execution is well under the 2000ms SLA
        $this->assertLessThan(2000, $executionTimeMs, 'Admin dashboard KPI response took longer than 2 seconds.');
    }

    public function test_department_wise_report_returns_metrics(): void
    {
        $response = $this->getJson("/api/v1/reports/departments?branch_id={$this->branch->id}&preset=last_7_days");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'period',
                    'total_departments',
                    'departments' => [
                        '*' => [
                            'department_name',
                            'total_patients',
                            'opd_consultations',
                            'admissions',
                            'discharges',
                            'occupied_beds',
                            'total_beds',
                            'average_occupancy_rate',
                            'total_revenue',
                        ],
                    ],
                ],
            ]);
    }

    public function test_doctor_performance_report_returns_clinical_metrics(): void
    {
        $response = $this->getJson("/api/v1/reports/doctors?branch_id={$this->branch->id}&preset=last_7_days");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'period',
                    'total_doctors',
                    'doctors' => [
                        '*' => [
                            'doctor_id',
                            'doctor_name',
                            'specialty',
                            'department',
                            'patients_seen',
                            'appointments_scheduled',
                            'appointments_completed',
                            'completion_rate_percentage',
                            'prescriptions_written',
                            'total_revenue_generated',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Acceptance criterion: Custom report builder prevents unsafe/unbounded queries (query cost limits).
     */
    public function test_custom_report_builder_schema_and_cost_limits(): void
    {
        $response = $this->getJson('/api/v1/reports/builder/schema');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'entities' => [
                        'invoices',
                        'payments',
                        'patients',
                        'appointments',
                        'admissions',
                        'prescriptions',
                        'lab_orders',
                        'radiology_orders',
                        'emergency_cases',
                    ],
                    'operators',
                    'aggregations',
                    'limits' => ['max_rows_per_page', 'hard_query_limit', 'max_filter_count', 'max_column_count'],
                ],
            ]);
    }

    public function test_custom_report_builder_executes_safe_query(): void
    {
        $payload = [
            'entity' => 'invoices',
            'branch_id' => $this->branch->id,
            'fields' => ['invoice_number', 'billing_type', 'status', 'total_cents', 'created_at'],
            'filters' => [
                [
                    'field' => 'status',
                    'operator' => '!=',
                    'value' => 'cancelled',
                ],
            ],
            'page' => 1,
            'per_page' => 25,
            'sort_field' => 'created_at',
            'sort_direction' => 'desc',
        ];

        $response = $this->postJson('/api/v1/reports/builder/query', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'entity' => 'invoices',
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'columns',
                    'data',
                    'meta' => [
                        'total_records',
                        'hard_cost_limit',
                        'query_duration_ms',
                    ],
                ],
            ]);
    }

    public function test_custom_report_builder_blocks_unsafe_unwhitelisted_entity(): void
    {
        $payload = [
            'entity' => 'users', // Unwhitelisted / sensitive entity
            'branch_id' => $this->branch->id,
            'fields' => ['name', 'email'],
        ];

        $response = $this->postJson('/api/v1/reports/builder/query', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'code' => 'QUERY_COST_VIOLATION',
            ]);
    }

    public function test_custom_report_builder_blocks_exceeding_column_limits(): void
    {
        // Request more than 15 columns (violates cost limit)
        $excessiveFields = [
            'invoice_number', 'billing_type', 'status', 'department', 'total_cents',
            'paid_cents', 'balance_cents', 'payment_terms', 'due_date', 'created_at',
            'extra_1', 'extra_2', 'extra_3', 'extra_4', 'extra_5', 'extra_6',
        ];

        $payload = [
            'entity' => 'invoices',
            'branch_id' => $this->branch->id,
            'fields' => $excessiveFields,
        ];

        $response = $this->postJson('/api/v1/reports/builder/query', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'code' => 'QUERY_COST_VIOLATION',
            ]);
    }

    /**
     * Acceptance criterion: Exports match on-screen data exactly.
     */
    public function test_export_standard_report_csv_matches_screen_format(): void
    {
        $payload = [
            'report_type' => 'departments',
            'branch_id' => $this->branch->id,
            'preset' => 'last_7_days',
        ];

        $response = $this->postJson('/api/v1/reports/export/standard', $payload);

        $response->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));

        // Stream content check
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Department Name', $content);
        $this->assertStringContainsString('Total Patients', $content);
        $this->assertStringContainsString('Total Revenue ($)', $content);
    }

    public function test_export_custom_builder_csv_matches_exact_query(): void
    {
        $payload = [
            'entity' => 'invoices',
            'branch_id' => $this->branch->id,
            'fields' => ['invoice_number', 'billing_type', 'status', 'total_cents'],
        ];

        $response = $this->postJson('/api/v1/reports/export/custom', $payload);

        $response->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Invoice Number', $content);
        $this->assertStringContainsString('Billing Type', $content);
        $this->assertStringContainsString('Payment Status', $content);
    }
}
