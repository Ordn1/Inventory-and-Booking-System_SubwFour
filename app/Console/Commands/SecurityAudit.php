<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\SecurityIncident;
use App\Models\SystemLog;

class SecurityAudit extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'security:audit
                            {--notify : Send notifications for critical issues}';

    /**
     * The console command description.
     */
    protected $description = 'Perform a security audit and report findings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting security audit...');
        $this->newLine();

        $issues = [];

        // Check for locked accounts
        $lockedAccounts = $this->checkLockedAccounts();
        if ($lockedAccounts > 0) {
            $issues[] = "Locked accounts: {$lockedAccounts}";
        }

        // Check for expired passwords
        $expiredPasswords = $this->checkExpiredPasswords();
        if ($expiredPasswords > 0) {
            $issues[] = "Expired passwords: {$expiredPasswords}";
        }

        // Check for accounts never changing password
        $neverChanged = $this->checkNeverChangedPasswords();
        if ($neverChanged > 0) {
            $issues[] = "Accounts that never changed password: {$neverChanged}";
        }

        // Check for recent security incidents
        $recentIncidents = $this->checkRecentSecurityIncidents();
        if ($recentIncidents > 0) {
            $issues[] = "Security incidents (last 24h): {$recentIncidents}";
        }

        // Check backup status
        $backupStatus = $this->checkBackupStatus();
        if (!$backupStatus) {
            $issues[] = "No recent backup found (older than 24h)";
        }

        // Report findings
        $this->newLine();
        if (empty($issues)) {
            $this->info('✓ Security audit completed - No issues found');
            
            SystemLog::security('Security audit passed', 'security_audit', [
                'status' => 'passed',
                'timestamp' => now()->toIso8601String(),
            ]);
        } else {
            $this->warn('Security audit completed with findings:');
            foreach ($issues as $issue) {
                $this->line("  - {$issue}");
            }
            
            SystemLog::security('Security audit findings', 'security_audit', [
                'status' => 'issues_found',
                'issues' => $issues,
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        return Command::SUCCESS;
    }

    /**
     * Check for locked accounts
     */
    protected function checkLockedAccounts(): int
    {
        $count = User::whereNotNull('locked_until')
            ->where('locked_until', '>', now())
            ->count();

        $this->line("Checking locked accounts... {$count} found");
        return $count;
    }

    /**
     * Check for expired passwords
     */
    protected function checkExpiredPasswords(): int
    {
        $expiryDays = config('security.password.expiry_days', 90);
        $expiredDate = now()->subDays($expiryDays);

        $count = User::where(function ($query) use ($expiredDate) {
            $query->whereNull('password_changed_at')
                  ->orWhere('password_changed_at', '<', $expiredDate);
        })->count();

        $this->line("Checking expired passwords... {$count} found");
        return $count;
    }

    /**
     * Check for accounts that never changed password
     */
    protected function checkNeverChangedPasswords(): int
    {
        $count = User::whereNull('password_changed_at')->count();
        
        $this->line("Checking accounts without password change... {$count} found");
        return $count;
    }

    /**
     * Check for recent security incidents
     */
    protected function checkRecentSecurityIncidents(): int
    {
        $count = SecurityIncident::where('created_at', '>=', now()->subDay())->count();
        
        $this->line("Checking recent security incidents... {$count} found");
        return $count;
    }

    /**
     * Check if recent backup exists
     */
    protected function checkBackupStatus(): bool
    {
        $backupPath = storage_path('app/backups');
        
        if (!is_dir($backupPath)) {
            $this->line("Checking backup status... No backup directory");
            return false;
        }

        $files = glob("{$backupPath}/backup_*");
        
        if (empty($files)) {
            $this->line("Checking backup status... No backups found");
            return false;
        }

        // Get most recent backup
        $latestFile = end($files);
        $fileTime = filemtime($latestFile);
        $age = (time() - $fileTime) / 3600; // Convert to hours

        $status = $age <= 24;
        $this->line("Checking backup status... Latest backup is " . round($age, 1) . " hours old");
        
        return $status;
    }
}
