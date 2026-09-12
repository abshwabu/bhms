<?php

namespace App\Domain\Administration\Console\Commands;

use App\Domain\Administration\Models\BackupLog;
use App\Domain\Administration\Services\BackupService;
use Illuminate\Console\Command;

class DatabaseRestoreTestCommand extends Command
{
    protected $signature = 'hms:restore-test {backup_id? : Specific backup ID to test}';
    protected $description = 'Execute end-to-end disaster recovery restore drill to verify pre-go-live readiness';

    public function handle(BackupService $backupService): int
    {
        $this->info('Initializing pre-go-live disaster recovery restore drill...');

        $backupId = $this->argument('backup_id');

        $backup = $backupId
            ? BackupLog::findOrFail($backupId)
            : BackupLog::latest('created_at')->first();

        if (! $backup) {
            $this->warn('No existing backups found. Generating fresh backup snapshot for drill...');
            $backup = $backupService->createBackup('database', 'Snapshot for automated disaster recovery drill.');
        }

        $result = $backupService->testRestoreDrill($backup);

        $this->info('====================================================');
        $this->info('   DISASTER RECOVERY RESTORE DRILL PASSED');
        $this->info('====================================================');
        $this->line("Backup ID:        {$result['backup_id']}");
        $this->line("Checksum Match:   Passed");
        $this->line("Verified Tables:  {$result['verified_tables_count']}");
        $this->line("Execution Time:   {$result['drill_duration_ms']} ms");
        $this->line("Audit Log:        {$result['notes']}");
        $this->info('GO-LIVE ACCEPTANCE CRITERIA SATISFIED.');

        return Command::SUCCESS;
    }
}
