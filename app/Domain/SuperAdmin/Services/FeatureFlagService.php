<?php

namespace App\Domain\SuperAdmin\Services;

use App\Domain\Shared\Models\Organization;
use App\Domain\SuperAdmin\Models\FeatureFlag;
use App\Domain\SuperAdmin\Models\TenantFeatureFlag;

class FeatureFlagService
{
    /**
     * Seed or verify the default master catalog of platform feature flags.
     */
    public function ensureDefaultFlagsExist(): void
    {
        $defaultFlags = [
            [
                'key' => 'telegram_reporting',
                'name' => 'Telegram Reporting & Alert Engine',
                'category' => 'integrations',
                'description' => 'Real-time Telegram emergency push, daily digests, and role-based staff bot commands.',
                'default_enabled_plans' => ['community', 'regional', 'enterprise'],
            ],
            [
                'key' => 'radiology_ris',
                'name' => 'Radiology Information System (RIS)',
                'category' => 'clinical',
                'description' => 'DICOM modality worklists, PACS study viewing, and radiologist report transcription.',
                'default_enabled_plans' => ['community', 'regional', 'enterprise'],
            ],
            [
                'key' => 'emergency_cad',
                'name' => 'Emergency Trauma & Ambulance CAD',
                'category' => 'clinical',
                'description' => 'ESI acuity triage classification, GPS CAD ambulance dispatch, and priority bed allocation.',
                'default_enabled_plans' => ['community', 'regional', 'enterprise'],
            ],
            [
                'key' => 'hr_rostering',
                'name' => 'HR & Staff Duty Rostering',
                'category' => 'operations',
                'description' => 'Shift conflict checking, doctor attendance tracking, and license credential renewal expiry alerts.',
                'default_enabled_plans' => ['community', 'regional', 'enterprise'],
            ],
            [
                'key' => 'inventory_assets',
                'name' => 'Medical Equipment & Asset Maintenance',
                'category' => 'operations',
                'description' => 'Preventive maintenance schedules, purchase order approval chain, and vendor management.',
                'default_enabled_plans' => ['community', 'regional', 'enterprise'],
            ],
            [
                'key' => 'bi_analytics',
                'name' => 'Executive BI & Custom Reporting',
                'category' => 'intelligence',
                'description' => 'Pre-aggregated sub-2s dashboards, custom report query builder, and PDF/Excel data exports.',
                'default_enabled_plans' => ['community', 'regional', 'enterprise'],
            ],
            [
                'key' => 'multi_branch',
                'name' => 'Multi-Branch Tenant Federation',
                'category' => 'operations',
                'description' => 'Satellite clinic and multi-hospital branch isolation with consolidated master data.',
                'default_enabled_plans' => ['regional', 'enterprise'],
            ],
        ];

        foreach ($defaultFlags as $flagData) {
            FeatureFlag::firstOrCreate(
                ['key' => $flagData['key']],
                $flagData
            );
        }
    }

    /**
     * Check if a feature is enabled for a given tenant organization.
     */
    public function isFeatureActiveForTenant(string $organizationId, string $featureKey): bool
    {
        $flag = FeatureFlag::where('key', $featureKey)->first();

        // If flag does not exist, seed default flags or permit
        if (!$flag) {
            $this->ensureDefaultFlagsExist();
            $flag = FeatureFlag::where('key', $featureKey)->first();
            if (!$flag) {
                return true;
            }
        }

        // If flag is globally turned off by vendor operator
        if (!$flag->is_globally_enabled) {
            return false;
        }

        // Check if tenant has an explicit override set
        $override = TenantFeatureFlag::where('organization_id', $organizationId)
            ->where('feature_flag_id', $flag->id)
            ->first();

        if ($override !== null) {
            return (bool) $override->is_enabled;
        }

        // Fallback to tenant organization plan tier
        $org = Organization::find($organizationId);
        $plan = $org?->plan_tier ?? 'community';

        $defaultPlans = $flag->default_enabled_plans ?? [];
        return in_array($plan, $defaultPlans, true) || in_array('*', $defaultPlans, true);
    }

    /**
     * Set explicit enable/disable override for a tenant.
     */
    public function setTenantFeatureFlag(string $organizationId, string $featureKey, bool $isEnabled, array $customConfig = []): TenantFeatureFlag
    {
        $flag = FeatureFlag::where('key', $featureKey)->firstOrFail();

        return TenantFeatureFlag::updateOrCreate(
            [
                'organization_id' => $organizationId,
                'feature_flag_id' => $flag->id,
            ],
            [
                'is_enabled' => $isEnabled,
                'custom_config' => $customConfig,
            ]
        );
    }

    /**
     * Get matrix of all features and their resolved active state for a tenant.
     */
    public function getTenantFlagsMatrix(string $organizationId): array
    {
        $this->ensureDefaultFlagsExist();

        $flags = FeatureFlag::orderBy('category')->orderBy('name')->get();
        $org = Organization::find($organizationId);
        $plan = $org?->plan_tier ?? 'community';

        $overrides = TenantFeatureFlag::where('organization_id', $organizationId)
            ->pluck('is_enabled', 'feature_flag_id')
            ->toArray();

        $matrix = [];
        foreach ($flags as $flag) {
            $hasOverride = array_key_exists($flag->id, $overrides);
            $isEnabled = $hasOverride 
                ? (bool) $overrides[$flag->id] 
                : (in_array($plan, $flag->default_enabled_plans ?? [], true) || in_array('*', $flag->default_enabled_plans ?? [], true));

            $matrix[] = [
                'flag_id' => $flag->id,
                'key' => $flag->key,
                'name' => $flag->name,
                'category' => $flag->category,
                'description' => $flag->description,
                'is_globally_enabled' => $flag->is_globally_enabled,
                'default_plans' => $flag->default_enabled_plans,
                'is_enabled' => $flag->is_globally_enabled && $isEnabled,
                'has_override' => $hasOverride,
            ];
        }

        return $matrix;
    }
}
