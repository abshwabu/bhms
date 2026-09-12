<?php

namespace Tests\Feature;

use App\Domain\Inventory\Models\Equipment;
use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\Vendor;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryDomainTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected Branch $branch;
    protected User $user;

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
        ]);

        Sanctum::actingAs($this->user);
    }

    /**
     * Test 1: Vendor management.
     */
    public function test_can_create_and_list_vendors(): void
    {
        $response = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson('/api/v1/inventory/vendors', [
                'organization_id' => $this->organization->id,
                'branch_id' => $this->branch->id,
                'vendor_code' => 'VEND-TEST-1',
                'name' => 'BioCare Instruments',
                'contact_name' => 'Sarah Connor',
                'email' => 'sales@biocare.com',
                'phone' => '+1555000111',
                'payment_terms' => 'net_30',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.vendor_code', 'VEND-TEST-1')
            ->assertJsonPath('data.name', 'BioCare Instruments');

        $list = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->getJson('/api/v1/inventory/vendors');

        $list->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test 2: Inventory tracking (medical & non-medical) and low-stock alerts.
     */
    public function test_can_track_medical_and_non_medical_inventory_with_alerts(): void
    {
        // 1. Create medical item with low stock
        $medItem = InventoryItem::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'item_code' => 'MED-GLV-01',
            'name' => 'Latex Gloves Medium',
            'category' => 'medical',
            'unit_of_measure' => 'box',
            'current_stock' => 5,
            'min_stock_level' => 20,
            'reorder_quantity' => 50,
            'unit_cost_cents' => 1500,
            'is_active' => true,
        ]);

        // 2. Create non-medical item with adequate stock
        $nonMedItem = InventoryItem::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'item_code' => 'NON-SHT-01',
            'name' => 'White Bed Linen',
            'category' => 'non_medical',
            'unit_of_measure' => 'piece',
            'current_stock' => 100,
            'min_stock_level' => 25,
            'reorder_quantity' => 50,
            'unit_cost_cents' => 1200,
            'is_active' => true,
        ]);

        // Query low stock alerts
        $alertsRes = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->getJson('/api/v1/inventory/alerts/low-stock');

        $alertsRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $alertsRes->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('MED-GLV-01', $data[0]['item_code']);
        $this->assertEquals('low_stock', $data[0]['stock_status']);
    }

    /**
     * Test 3: Stock adjustments and departmental consumption with reconciliation.
     */
    public function test_stock_adjustments_and_consumption_reconcile_properly(): void
    {
        $item = InventoryItem::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'item_code' => 'MED-SYR-01',
            'name' => 'Syringes 5ml',
            'category' => 'medical',
            'unit_of_measure' => 'box',
            'current_stock' => 50,
            'min_stock_level' => 10,
            'unit_cost_cents' => 1000,
            'is_active' => true,
        ]);

        // Consume 15 units in emergency department
        $consumeRes = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/inventory/items/{$item->id}/consume", [
                'quantity' => 15,
                'department' => 'Emergency Department',
                'notes' => 'Triage bay usage',
            ]);

        $consumeRes->assertStatus(200);
        $item->refresh();
        $this->assertEquals(35, $item->current_stock);

        // Adjust stock manually (+10 units)
        $adjustRes = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/inventory/items/{$item->id}/adjust", [
                'quantity' => 10,
                'type' => 'adjustment_addition',
                'notes' => 'Found unopened box in transit',
            ]);

        $adjustRes->assertStatus(200);
        $item->refresh();
        $this->assertEquals(45, $item->current_stock);

        // Reconcile
        $recRes = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->getJson("/api/v1/inventory/items/{$item->id}/reconcile");

        $recRes->assertStatus(200)
            ->assertJsonPath('data.total_consumed_by_departments', 15)
            ->assertJsonPath('data.net_manual_adjustments', 10)
            ->assertJsonPath('data.total_movement_records', 2);
    }

    /**
     * Test 4: Purchase order approval chain and stock receipt.
     * Acceptance criterion: Purchase orders follow a defined approval chain before stock is received.
     */
    public function test_purchase_order_approval_chain_and_stock_receipt(): void
    {
        $vendor = Vendor::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'vendor_code' => 'VEND-PO-1',
            'name' => 'PharmaSupply Co',
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'item_code' => 'MED-GAU-01',
            'name' => 'Gauze 4x4',
            'category' => 'medical',
            'unit_of_measure' => 'pack',
            'current_stock' => 10,
            'unit_cost_cents' => 800,
            'is_active' => true,
        ]);

        // 1. Create draft PO
        $createRes = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson('/api/v1/inventory/purchase-orders', [
                'organization_id' => $this->organization->id,
                'branch_id' => $this->branch->id,
                'vendor_id' => $vendor->id,
                'items' => [
                    [
                        'inventory_item_id' => $item->id,
                        'quantity_ordered' => 40,
                        'unit_cost_cents' => 800,
                    ],
                ],
            ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('data.status', 'draft');

        $poId = $createRes->json('data.id');
        $po = PurchaseOrder::findOrFail($poId);
        $poItemId = $po->items->first()->id;

        // 2. Receiving on draft must be REJECTED by domain
        $prematureReceive = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/inventory/purchase-orders/{$poId}/receive", [
                'items' => [
                    [
                        'purchase_order_item_id' => $poItemId,
                        'quantity_received' => 20,
                    ],
                ],
            ]);

        $prematureReceive->assertStatus(422);

        // 3. Submit PO for approval
        $submitRes = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/inventory/purchase-orders/{$poId}/submit");

        $submitRes->assertStatus(200)
            ->assertJsonPath('data.status', 'submitted');

        // 4. Approve PO
        $approveRes = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/inventory/purchase-orders/{$poId}/approve");

        $approveRes->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        // 5. Receive 20 items (partial receipt)
        $receiveRes = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/inventory/purchase-orders/{$poId}/receive", [
                'items' => [
                    [
                        'purchase_order_item_id' => $poItemId,
                        'quantity_received' => 20,
                    ],
                ],
            ]);

        $receiveRes->assertStatus(200)
            ->assertJsonPath('data.status', 'partially_received');

        // Verify stock incremented from 10 to 30
        $item->refresh();
        $this->assertEquals(30, $item->current_stock);

        // 6. Receive remaining 20 items (completes PO)
        $completeReceiveRes = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/inventory/purchase-orders/{$poId}/receive", [
                'items' => [
                    [
                        'purchase_order_item_id' => $poItemId,
                        'quantity_received' => 20,
                    ],
                ],
            ]);

        $completeReceiveRes->assertStatus(200)
            ->assertJsonPath('data.status', 'received');

        $item->refresh();
        $this->assertEquals(50, $item->current_stock);
    }

    /**
     * Test 5: Equipment maintenance scheduling and alerts ahead of due dates.
     * Acceptance criterion: Maintenance schedules generate reminders/alerts ahead of due dates.
     */
    public function test_equipment_maintenance_scheduling_and_alerts(): void
    {
        // 1. Create equipment due in 5 days
        $equipment = Equipment::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'asset_tag' => 'EQ-VENT-01',
            'name' => 'Intensive Care Ventilator',
            'category' => 'biomedical',
            'department' => 'icu',
            'maintenance_frequency_days' => 90,
            'next_maintenance_date' => Carbon::now()->addDays(5),
            'status' => 'operational',
            'criticality' => 'critical',
        ]);

        // 2. Query alerts
        $alertsRes = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->getJson('/api/v1/inventory/equipment/maintenance-alerts?upcoming_days=14');

        $alertsRes->assertStatus(200)
            ->assertJsonPath('data.upcoming_equipment_count', 1)
            ->assertJsonPath('data.overdue_equipment_count', 0);

        // 3. Schedule maintenance
        $schedRes = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/inventory/equipment/{$equipment->id}/schedule-maintenance", [
                'scheduled_date' => Carbon::now()->addDays(5)->toDateString(),
                'maintenance_type' => 'calibration',
                'priority' => 'high',
                'technician_name' => 'John Biomed',
            ]);

        $schedRes->assertStatus(201);
        $logId = $schedRes->json('data.id');

        // 4. Complete maintenance
        $completeRes = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/inventory/maintenance-logs/{$logId}/complete", [
                'completed_date' => Carbon::today()->toDateString(),
                'actions_taken' => 'Oxygen pressure calibration performed and certified.',
                'cost_cents' => 25000,
            ]);

        $completeRes->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');

        $equipment->refresh();
        $this->assertEquals(Carbon::today()->toDateString(), $equipment->last_maintenance_date->toDateString());
        $this->assertEquals('operational', $equipment->status);
    }
}
