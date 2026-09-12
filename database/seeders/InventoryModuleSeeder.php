<?php

namespace Database\Seeders;

use App\Domain\Inventory\Models\Equipment;
use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Inventory\Models\MaintenanceLog;
use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\PurchaseOrderItem;
use App\Domain\Inventory\Models\Vendor;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InventoryModuleSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::first();
        $branch = Branch::first();
        $user = User::first();

        if (!$organization || !$branch || !$user) {
            return;
        }

        // 1. Seed Vendors
        $v1 = Vendor::updateOrCreate(
            ['branch_id' => $branch->id, 'vendor_code' => 'VEND-001'],
            [
                'organization_id' => $organization->id,
                'name' => 'MedTech Surgical & Lab Supplies',
                'contact_name' => 'Alice Morgan',
                'email' => 'orders@medtechsupplies.com',
                'phone' => '+1 (555) 234-5678',
                'tax_id' => 'TX-998822',
                'payment_terms' => 'net_30',
                'rating' => 4.8,
                'is_active' => true,
                'notes' => 'Primary distributor for disposable medical and surgical consumables.',
            ]
        );

        $v2 = Vendor::updateOrCreate(
            ['branch_id' => $branch->id, 'vendor_code' => 'VEND-002'],
            [
                'organization_id' => $organization->id,
                'name' => 'Apex Biomedical Engineering & Imaging Services',
                'contact_name' => 'Marcus Vance',
                'email' => 'service@apexbiomed.com',
                'phone' => '+1 (555) 987-6543',
                'tax_id' => 'TX-443311',
                'payment_terms' => 'net_15',
                'rating' => 4.9,
                'is_active' => true,
                'notes' => 'Contracted biomedical equipment maintenance, calibration, and warranty support.',
            ]
        );

        $v3 = Vendor::updateOrCreate(
            ['branch_id' => $branch->id, 'vendor_code' => 'VEND-003'],
            [
                'organization_id' => $organization->id,
                'name' => 'Metro Hospital Linens & Facility Care',
                'contact_name' => 'Brenda Cooper',
                'email' => 'support@metrolinens.com',
                'phone' => '+1 (555) 432-8765',
                'tax_id' => 'TX-112233',
                'payment_terms' => 'net_30',
                'rating' => 4.5,
                'is_active' => true,
                'notes' => 'Hospital bedding, sanitary supplies, and PPE apparel.',
            ]
        );

        // 2. Seed Medical and Non-Medical Inventory Items
        $itemsData = [
            [
                'item_code' => 'MED-GLV-001',
                'name' => 'Sterile Surgical Gloves (Latex Size 7.5)',
                'category' => 'medical',
                'sub_category' => 'ppe',
                'unit_of_measure' => 'box',
                'current_stock' => 85,
                'min_stock_level' => 30,
                'reorder_quantity' => 100,
                'unit_cost_cents' => 1850, // $18.50 / box
                'default_vendor_id' => $v1->id,
                'storage_location' => 'Main Warehouse - Aisle 2, Shelf B',
            ],
            [
                'item_code' => 'MED-SYR-005',
                'name' => 'Luer-Lock Syringes 5mL (Box of 100)',
                'category' => 'medical',
                'sub_category' => 'surgical_supplies',
                'unit_of_measure' => 'box',
                'current_stock' => 12, // LOW STOCK
                'min_stock_level' => 25,
                'reorder_quantity' => 50,
                'unit_cost_cents' => 1400, // $14.00
                'default_vendor_id' => $v1->id,
                'storage_location' => 'Central Pharmacy Supply - Rack 4',
            ],
            [
                'item_code' => 'MED-CAN-020',
                'name' => 'IV Cannula with Injection Port 20G',
                'category' => 'medical',
                'sub_category' => 'surgical_supplies',
                'unit_of_measure' => 'box',
                'current_stock' => 6, // LOW STOCK
                'min_stock_level' => 20,
                'reorder_quantity' => 40,
                'unit_cost_cents' => 2200, // $22.00
                'default_vendor_id' => $v1->id,
                'storage_location' => 'Emergency Room Stockroom',
            ],
            [
                'item_code' => 'MED-GAU-004',
                'name' => 'Sterile Gauze Swabs 4x4 (Pack of 200)',
                'category' => 'medical',
                'sub_category' => 'wound_care',
                'unit_of_measure' => 'pack',
                'current_stock' => 120,
                'min_stock_level' => 40,
                'reorder_quantity' => 80,
                'unit_cost_cents' => 950, // $9.50
                'default_vendor_id' => $v1->id,
                'storage_location' => 'OT Prep Room A',
            ],
            [
                'item_code' => 'MED-N95-001',
                'name' => 'N95 Particulate Respirator Masks (Box of 50)',
                'category' => 'medical',
                'sub_category' => 'ppe',
                'unit_of_measure' => 'box',
                'current_stock' => 45,
                'min_stock_level' => 20,
                'reorder_quantity' => 50,
                'unit_cost_cents' => 3500, // $35.00
                'default_vendor_id' => $v3->id,
                'storage_location' => 'Infection Control Cache',
            ],
            [
                'item_code' => 'NON-SHT-001',
                'name' => 'Fitted Hospital Bed Sheets (Cotton)',
                'category' => 'non_medical',
                'sub_category' => 'linens',
                'unit_of_measure' => 'piece',
                'current_stock' => 150,
                'min_stock_level' => 50,
                'reorder_quantity' => 100,
                'unit_cost_cents' => 1200, // $12.00
                'default_vendor_id' => $v3->id,
                'storage_location' => 'Laundry & Housekeeping Depo',
            ],
            [
                'item_code' => 'NON-SAN-005',
                'name' => 'Hospital Grade Surface Disinfectant 5L',
                'category' => 'non_medical',
                'sub_category' => 'sanitation',
                'unit_of_measure' => 'bottle',
                'current_stock' => 4, // LOW STOCK
                'min_stock_level' => 15,
                'reorder_quantity' => 25,
                'unit_cost_cents' => 2800, // $28.00
                'default_vendor_id' => $v3->id,
                'storage_location' => 'Sanitation Locker Room',
            ],
            [
                'item_code' => 'NON-TON-085',
                'name' => 'High-Yield Laser Printer Toner Cartridge',
                'category' => 'non_medical',
                'sub_category' => 'it_supplies',
                'unit_of_measure' => 'piece',
                'current_stock' => 8,
                'min_stock_level' => 5,
                'reorder_quantity' => 10,
                'unit_cost_cents' => 6500, // $65.00
                'default_vendor_id' => $v1->id,
                'storage_location' => 'IT Admin Storage',
            ],
        ];

        $createdItems = [];
        foreach ($itemsData as $data) {
            $item = InventoryItem::updateOrCreate(
                ['branch_id' => $branch->id, 'item_code' => $data['item_code']],
                array_merge($data, [
                    'organization_id' => $organization->id,
                    'is_active' => true,
                ])
            );
            $createdItems[$data['item_code']] = $item;
        }

        // 3. Seed Purchase Orders in different approval workflow states
        // PO 1: Approved & Partially Received
        $po1 = PurchaseOrder::updateOrCreate(
            ['po_number' => 'PO-2026-0001'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'vendor_id' => $v1->id,
                'status' => 'partially_received',
                'order_date' => Carbon::now()->subDays(5),
                'expected_delivery_date' => Carbon::now()->addDays(2),
                'subtotal_cents' => 96500,
                'tax_cents' => 4825,
                'shipping_cost_cents' => 2500,
                'total_cents' => 103825,
                'currency' => 'USD',
                'created_by' => $user->id,
                'submitted_by' => $user->id,
                'submitted_at' => Carbon::now()->subDays(4),
                'approved_by' => $user->id,
                'approved_at' => Carbon::now()->subDays(3),
                'notes' => 'Urgent replenishment of surgical syringes and cannulas.',
            ]
        );

        $syr = $createdItems['MED-SYR-005'];
        PurchaseOrderItem::updateOrCreate(
            ['purchase_order_id' => $po1->id, 'inventory_item_id' => $syr->id],
            [
                'quantity_ordered' => 50,
                'quantity_received' => 20, // 20 received out of 50
                'unit_cost_cents' => $syr->unit_cost_cents,
                'total_cost_cents' => 50 * $syr->unit_cost_cents,
            ]
        );

        // Record audit movement for received 20 units
        InventoryMovement::firstOrCreate(
            ['reference_id' => $po1->id, 'inventory_item_id' => $syr->id, 'movement_type' => 'purchase_receipt'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'quantity' => 20,
                'quantity_before' => 0,
                'quantity_after' => 20,
                'unit_cost_cents' => $syr->unit_cost_cents,
                'total_cost_cents' => 20 * $syr->unit_cost_cents,
                'reference_type' => 'purchase_order',
                'department' => 'Central Warehouse',
                'performed_by' => $user->id,
                'notes' => "Received partial delivery (20/50 units) from PO #PO-2026-0001",
            ]
        );

        // PO 2: Submitted (Awaiting Supervisor Sign-off)
        PurchaseOrder::updateOrCreate(
            ['po_number' => 'PO-2026-0002'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'vendor_id' => $v3->id,
                'status' => 'submitted',
                'order_date' => Carbon::now()->subDays(1),
                'expected_delivery_date' => Carbon::now()->addDays(5),
                'subtotal_cents' => 190000,
                'tax_cents' => 9500,
                'shipping_cost_cents' => 5000,
                'total_cents' => 204500,
                'currency' => 'USD',
                'created_by' => $user->id,
                'submitted_by' => $user->id,
                'submitted_at' => Carbon::now()->subHours(6),
                'notes' => 'Restocking hospital linens and disinfectant canisters.',
            ]
        );

        // 4. Seed Hospital Equipment & Biomedical Assets
        // Equipment 1: Urgent Overdue Maintenance
        $eq1 = Equipment::updateOrCreate(
            ['branch_id' => $branch->id, 'asset_tag' => 'EQ-BIO-001'],
            [
                'organization_id' => $organization->id,
                'name' => 'Mindray SV300 ICU Ventilator',
                'category' => 'biomedical',
                'model_number' => 'SV300',
                'serial_number' => 'MRY-VNT-88910',
                'manufacturer' => 'Mindray Biomedical',
                'vendor_id' => $v2->id,
                'department' => 'icu',
                'room_location' => 'ICU Bay 02',
                'purchase_date' => Carbon::now()->subYears(2),
                'purchase_cost_cents' => 2450000, // $24,500.00
                'warranty_expiry_date' => Carbon::now()->addMonths(6),
                'status' => 'under_maintenance',
                'criticality' => 'critical',
                'maintenance_frequency_days' => 90,
                'last_maintenance_date' => Carbon::now()->subDays(95),
                'next_maintenance_date' => Carbon::now()->subDays(5), // 5 days overdue!
                'notes' => 'High-acuity life-support mechanical ventilator. Requires flow sensor recalibration.',
            ]
        );

        // Equipment 2: Maintenance Due Soon (in 4 days)
        $eq2 = Equipment::updateOrCreate(
            ['branch_id' => $branch->id, 'asset_tag' => 'EQ-BIO-002'],
            [
                'organization_id' => $organization->id,
                'name' => 'GE Healthcare B40 Patient Multiparameter Monitor',
                'category' => 'biomedical',
                'model_number' => 'B40-PRO',
                'serial_number' => 'GE-MON-44120',
                'manufacturer' => 'GE Healthcare',
                'vendor_id' => $v2->id,
                'department' => 'ot',
                'room_location' => 'Operation Theatre Suite 1',
                'purchase_date' => Carbon::now()->subYear(),
                'purchase_cost_cents' => 890000, // $8,900.00
                'warranty_expiry_date' => Carbon::now()->addMonths(12),
                'status' => 'operational',
                'criticality' => 'high',
                'maintenance_frequency_days' => 90,
                'last_maintenance_date' => Carbon::now()->subDays(86),
                'next_maintenance_date' => Carbon::now()->addDays(4), // Due in 4 days!
                'notes' => 'ECG, SpO2, NIBP parameters. Scheduled for quarterly PM battery & lead inspection.',
            ]
        );

        // Equipment 3: Operational Ultrasound
        $eq3 = Equipment::updateOrCreate(
            ['branch_id' => $branch->id, 'asset_tag' => 'EQ-RAD-003'],
            [
                'organization_id' => $organization->id,
                'name' => 'Philips ClearVue 350 Diagnostic Ultrasound',
                'category' => 'radiology',
                'model_number' => 'ClearVue-350',
                'serial_number' => 'PH-US-10294',
                'manufacturer' => 'Philips Medical Systems',
                'vendor_id' => $v2->id,
                'department' => 'radiology',
                'room_location' => 'Sonography Room 3',
                'purchase_date' => Carbon::now()->subMonths(18),
                'purchase_cost_cents' => 4500000, // $45,000.00
                'warranty_expiry_date' => Carbon::now()->addDays(20), // Warranty expiring soon!
                'status' => 'operational',
                'criticality' => 'high',
                'maintenance_frequency_days' => 180,
                'last_maintenance_date' => Carbon::now()->subDays(60),
                'next_maintenance_date' => Carbon::now()->addDays(120),
                'notes' => 'General abdominal and vascular probe transducers.',
            ]
        );

        // 5. Seed Maintenance Schedules & Logs
        MaintenanceLog::updateOrCreate(
            ['log_number' => 'MNT-2026-0001'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'equipment_id' => $eq1->id,
                'maintenance_type' => 'calibration',
                'status' => 'scheduled',
                'priority' => 'urgent',
                'scheduled_date' => Carbon::now()->subDays(5),
                'technician_name' => 'Danielle Vance, CBMET',
                'vendor_id' => $v2->id,
                'performed_by' => $user->id,
                'cost_cents' => 35000, // $350.00
                'findings' => 'Flow transducer zero-offset drift detected during morning self-test.',
            ]
        );

        MaintenanceLog::updateOrCreate(
            ['log_number' => 'MNT-2026-0002'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'equipment_id' => $eq2->id,
                'maintenance_type' => 'preventive',
                'status' => 'scheduled',
                'priority' => 'medium',
                'scheduled_date' => Carbon::now()->addDays(4),
                'technician_name' => 'Internal Biomedical Tech Team',
                'vendor_id' => $v2->id,
                'cost_cents' => 15000, // $150.00
                'findings' => 'Scheduled Q3 battery discharge & electrical safety analyzer verification.',
            ]
        );
    }
}
