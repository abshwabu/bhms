# Multi-Tenancy & Multi-Branch Architecture Strategy

**Project:** Hospital Management System (HMS)  
**Database:** PostgreSQL 16+ with Row-Level Security (RLS) & Laravel Global Scopes  
**Strategy Selected:** **Tenant-Scoped Rows with Shared Schema & Branch Isolation Middleware**

---

## 1. Architectural Decision: Tenant-Scoped Rows vs. Schema-Per-Tenant

For a multi-hospital chain/network, the system architecture utilizes a **Tenant-Scoped Rows (Shared Schema)** strategy backed by deterministic composite indexes, foreign key constraints, and automatic query scoping.

### Comparative Evaluation

| Evaluation Criteria | Schema-Per-Tenant (Separate PostgreSQL Schemas) | Tenant-Scoped Rows (Shared Schema with `branch_id`) |
| :--- | :--- | :--- |
| **Clinical Continuity & Patient Transfers** | ❌ Complex cross-schema queries or data copying when a patient is transferred between branches or referred to specialists. | ✅ **Seamless.** Patient Master Record (`mrn`) is unified across the enterprise while encounters, admissions, and orders are isolated by `branch_id`. |
| **Doctor & Staff Mobility** | ❌ Clinicians working across multiple hospital facilities must be duplicated across schemas, complicating credentials and scheduling. | ✅ **Unified Identity.** Clinicians belong to the holding organization with affiliations to multiple branches via `user_branch_access`. |
| **Consolidated Holding Analytics** | ❌ Aggregating executive KPIs across 20+ branches requires querying 20+ disparate schemas and merging results. | ✅ **High-Performance.** Group-wide BI aggregation queries run natively using materialized views and branch partitions. |
| **Migration & Schema Maintenance** | ❌ High risk of schema drift. Migrations must iterate through every tenant schema sequentially. | ✅ **Zero Drift.** Migrations run once atomically in standard CI/CD deployment pipelines. |
| **Data Isolation & Security** | Strong boundary at schema level. | ✅ **Guaranteed Isolation.** Triple-barrier enforcement: `BranchScopeMiddleware` + Eloquent `BranchScope` + composite foreign keys. |

---

## 2. Multi-Tier Tenancy Hierarchy

```
Organization (Holding Group / Enterprise e.g. Metro Health System)
 └── Branch / Operating Facility (Hospital Campus, Regional Clinic, Diagnostic Center)
      ├── Departments (Cardiology, Emergency, Radiology, Pharmacy)
      ├── Services Catalog & Price Lists
      ├── Operational Encounters (OPD Visits, Inpatient Admissions, Triage)
      └── Staff Duty Rosters & Ward Beds
```

1. **Organization Tier (`organizations` table):** Top-level corporate or holding entity. Holds enterprise-wide default configurations, taxation rules, currency standards, and master brand identity.
2. **Branch / Facility Tier (`branches` table):** Primary operational unit (e.g., Central Hospital, North Suburban Clinic). Every clinical record, inventory lot, invoice, and audit log is partitioned by `branch_id`.

---

## 3. Data Isolation Mechanisms (Zero Cross-Branch Leakage)

Cross-branch data leakage is strictly prevented through a multi-layered defence:

### Layer 1: HTTP Request Boundary (`BranchScopeMiddleware`)
- Every API request carries the `X-Branch-ID` HTTP header, or falls back to the authenticated user's `default_branch_id`.
- The middleware validates whether the requesting user has explicit access to that branch via `user_branch_access`.
- Unauthorized requests attempting to query a foreign branch are immediately terminated with `403 Forbidden` (`BRANCH_UNAUTHORIZED`).
- Validated branch context is bound to the service container: `current_branch_id` and `current_organization_id`.
- Spatie's permission team context is set via `setPermissionsTeamId($branch->id)`.

### Layer 2: Eloquent Global Query Scopes (`BranchScope`)
- All branch-aware models implement the `BelongsToBranch` trait.
- `BelongsToBranch` attaches `BranchScope` to automatically inject `WHERE {table}.branch_id = current_branch_id` on all `SELECT`, `UPDATE`, and `DELETE` queries.
- When creating new records, `branch_id` and `organization_id` are automatically populated from the active container context if not explicitly provided.

### Layer 3: Database-Level Integrity Constraints
- Unique indexes are scoped per branch:
  - `departments`: `UNIQUE (branch_id, code)`
  - `services`: `UNIQUE (branch_id, code)`
  - `price_lists`: `UNIQUE (branch_id, code)`
  - `ward_beds`: `UNIQUE (ward_id, bed_number)`
- Foreign keys enforce cascade or restrict policies linked to parent branches.

---

## 4. Cross-Branch Operational Capabilities

While clinical and financial records are isolated, the architecture provides authorized cross-facility workflows:
- **Patient Referrals (`ReferralManager.vue`):** Patients can be transferred from Branch A (community clinic) to Branch B (tertiary hospital) with explicit clinician handoff.
- **Enterprise Reporting (`ReportsMasterView.vue`):** System administrators can switch branch contexts or view group-wide KPI dashboards across all operating facilities.
