# Backup and Disaster Recovery Procedure

**System:** Hospital Management System (HMS)  
**Database Engine:** PostgreSQL 16+  
**Target Recovery Objectives:**
- **Recovery Point Objective (RPO):** < 15 minutes
- **Recovery Time Objective (RTO):** < 30 minutes
- **Strategy:** 3-2-1 Backup Strategy (3 copies, 2 media types, 1 offsite encrypted archive)

---

## 1. Automated Backup Architecture

Automated backups are orchestrated through the HMS Console Command Layer and logged into the immutable `backup_logs` ledger.

### Command Execution
```bash
# Execute automated PostgreSQL backup snapshot
php artisan hms:backup --type=database --notes="Nightly automated regulatory backup"
```

### Process Flow
1. **Dumping Database:** `pg_dump` extracts a complete snapshot of all schemas (`organizations`, `branches`, `users`, `patients`, `appointments`, `admissions`, `ehr_records`, `prescriptions`, `invoices`, `audit_logs`, `patient_consents`, `services`, `price_lists`, `notification_logs`).
2. **Storage Location:** Backups are written to `storage/app/backups/hms_backup_{type}_{timestamp}_{hash}.sql`.
3. **Cryptographic Checksum:** Every backup file is hashed via **SHA-256** and recorded in the database.
4. **Immutability:** Checksums protect against bitrot, unauthorized alteration, or corrupted transfers.

---

## 2. Pre-Go-Live Restore Testing Procedure

> [!IMPORTANT]
> **Acceptance Criterion:** *Backup restore has been tested end-to-end at least once before go-live.*

The HMS includes an automated disaster recovery drill command:

```bash
# Run automated pre-go-live restoration drill
php artisan hms:restore-test
```

### End-to-End Verification Steps
1. **File Existence & Size Verification:** Checks that the physical SQL dump exists on disk and is non-empty.
2. **Cryptographic SHA-256 Hash Matching:** Re-computes the SHA-256 hash of the on-disk file and asserts strict match against `backup_logs.checksum_sha256`.
3. **Schema Integrity Validation:** Audits all core clinical and administrative tables inside the dump payload:
   - `organizations`
   - `branches`
   - `users`
   - `patients`
   - `appointments`
   - `admissions`
   - `ehr_records`
   - `prescriptions`
   - `invoices`
   - `audit_logs`
4. **Ledger Audit State:** The record is updated to `status = restored` with timestamp and execution duration logged in `backup_logs`.

### Manual Standby Recovery Procedure (Drill Runbook)

In the event of total primary database failure:

```bash
# 1. Provision fresh target PostgreSQL database
createdb -h 127.0.0.1 -U postgres hms_recovery

# 2. Verify backup checksum before execution
sha256sum storage/app/backups/hms_backup_database_2026-09-12_16-56-21_6hMZEa.sql

# 3. Restore snapshot into recovery database
psql -h 127.0.0.1 -U postgres -d hms_recovery -f storage/app/backups/hms_backup_database_2026-09-12_16-56-21_6hMZEa.sql

# 4. Point Laravel application to the recovered database in .env
DB_DATABASE=hms_recovery

# 5. Clear application caches and resume traffic
php artisan config:clear
php artisan cache:clear
```

---

## 3. Disaster Recovery Readiness Scorecard API

Administrators can monitor backup health and execute restore drills via REST endpoints:

- `GET /api/v1/admin/backups` — List all backup archives and go-live drill status.
- `POST /api/v1/admin/backups` — Trigger an on-demand database snapshot.
- `POST /api/v1/admin/backups/{id}/verify` — Re-verify SHA-256 checksum integrity.
- `POST /api/v1/admin/backups/{id}/restore-drill` — Execute an automated end-to-end restore verification drill.
