<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\SystemLog;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:database 
                            {--compress : Compress the backup file}
                            {--clean : Clean old backups beyond retention period}';

    /**
     * The console command description.
     */
    protected $description = 'Create a backup of the database and optionally clean old backups';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting database backup...');
        
        try {
            $filename = $this->createBackup();
            
            $this->info("Backup created: {$filename}");
            
            SystemLog::info('Database backup created', 'backup_created', [
                'filename' => $filename,
                'size' => Storage::disk('local')->size("backups/{$filename}"),
            ]);

            if ($this->option('clean')) {
                $this->cleanOldBackups();
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            
            SystemLog::error('Database backup failed', 'backup_failed', [
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Create the database backup
     */
    protected function createBackup(): string
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$timestamp}.sql";
        
        // Ensure backup directory exists
        $backupPath = storage_path('app/backups');
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        
        $filepath = "{$backupPath}/{$filename}";

        // For MySQL
        if ($connection === 'mysql' || $connection === 'mariadb') {
            $command = sprintf(
                'mysqldump -h%s -P%s -u%s -p%s %s > %s',
                escapeshellarg($config['host']),
                escapeshellarg($config['port'] ?? 3306),
                escapeshellarg($config['username']),
                escapeshellarg($config['password']),
                escapeshellarg($config['database']),
                escapeshellarg($filepath)
            );
            
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0) {
                throw new \Exception('mysqldump command failed');
            }
        }
        // For SQLite
        elseif ($connection === 'sqlite') {
            copy($config['database'], $filepath);
        }
        else {
            throw new \Exception("Unsupported database connection: {$connection}");
        }

        // Compress if requested
        if ($this->option('compress') && file_exists($filepath)) {
            $gzFilepath = "{$filepath}.gz";
            $fp = gzopen($gzFilepath, 'w9');
            gzwrite($fp, file_get_contents($filepath));
            gzclose($fp);
            unlink($filepath);
            $filename .= '.gz';
        }

        return $filename;
    }

    /**
     * Clean old backups beyond retention period
     */
    protected function cleanOldBackups(): void
    {
        $retentionDays = config('security.backup.retention_days', 30);
        $backupPath = storage_path('app/backups');
        
        if (!is_dir($backupPath)) {
            return;
        }

        $files = glob("{$backupPath}/backup_*");
        $deleted = 0;
        
        foreach ($files as $file) {
            $fileTime = filemtime($file);
            $age = (time() - $fileTime) / 86400; // Convert to days
            
            if ($age > $retentionDays) {
                unlink($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("Cleaned {$deleted} old backup(s)");
            
            SystemLog::info('Old backups cleaned', 'backup_cleanup', [
                'deleted_count' => $deleted,
                'retention_days' => $retentionDays,
            ]);
        }
    }
}
