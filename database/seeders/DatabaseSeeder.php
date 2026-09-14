<?php

namespace Database\Seeders;

use App\Domain\Auth\Services\AuthenticationService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with complete multi-domain demo data.
     */
    public function run(): void
    {
        // 1. Ensure Tenant Organization, Branches, RBAC Roles, and Staff Accounts exist
        app(AuthenticationService::class)->ensureDemoUsersExist();

        // 2. Execute all domain module seeders in dependency order
        $this->call([
            PatientModuleSeeder::class,
            IpdModuleSeeder::class,
            OpdModuleSeeder::class,
            ClinicalModuleSeeder::class,
            PharmacyModuleSeeder::class,
            LaboratoryModuleSeeder::class,
            RadiologyModuleSeeder::class,
            BillingModuleSeeder::class,
            EmergencyModuleSeeder::class,
            InventoryModuleSeeder::class,
            HrModuleSeeder::class,
            ReportsAnalyticsModuleSeeder::class,
        ]);
    }
}
