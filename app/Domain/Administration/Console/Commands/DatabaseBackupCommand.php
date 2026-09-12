<?php

namespace App\Domain\Administration\Console\Commands;

use App\Domain\Administration\Services\BackupService;
use Illuminate\Console\Command;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'hms:backup {--type=database : Type of backup} {--notes= : Optional backup notes}';
    protected $description = 'Trigger automated PostgreSQL backup snapshot with SHA-256 integrity verification';

    public function handle(BackupService $backupService): int
    {
        $this->info('Starting automated HMS database backup...');

        $type = $this->option('type') ?: 'database';
        $notes = $this->option('notes');

        $backup = $backupService->createBackup($type, $notes);

        $this->info("Backup created successfully: {$backup->file_path}");
        $this->info("Size: {$backup->file_size_bytes} bytes");
        $this->info("SHA-256 Checksum: {$backup->checksum_sha256}");

        return Command::SUCCESS;
    }
}
