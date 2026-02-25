<?php

namespace App\Services;

use App\Models\SecurityIncident;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Models\SystemLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IncidentDetectionService
{
    /**
     * Detection thresholds
     */
    protected array $thresholds;

    public function __construct()
    {
        $this->thresholds = [
            'failed_logins_per_ip' => 10,      // Per hour
            'failed_logins_per_user' => 5,     // Per hour
            'requests_per_minute' => 100,       // Rate limiting
            'suspicious_patterns_per_hour' => 3,
            'concurrent_sessions' => 5,
        ];
    }

    /**
     * Run all detection checks
     */
    public function runDetection(): array
    {
        $detectedIncidents = [];

        // Brute force detection
        $bruteForce = $this->detectBruteForceAttempts();
        if (!empty($bruteForce)) {
            $detectedIncidents['brute_force'] = $bruteForce;
        }

        // Distributed attack detection
        $distributed = $this->detectDistributedAttack();
        if ($distributed) {
            $detectedIncidents['distributed_attack'] = $distributed;
        }

        // Account compromise indicators
        $compromised = $this->detectAccountCompromise();
        if (!empty($compromised)) {
            $detectedIncidents['account_compromise'] = $compromised;
        }

        // Unusual activity patterns
        $unusual = $this->detectUnusualActivity();
        if (!empty($unusual)) {
            $detectedIncidents['unusual_activity'] = $unusual;
        }

        return $detectedIncidents;
    }

    /**
     * Detect brute force login attempts
     */
    public function detectBruteForceAttempts(): array
    {
        $incidents = [];
        $cutoff = now()->subHour();

        // Failed logins per IP
        $suspiciousIps = LoginAttempt::select('ip_address', DB::raw('COUNT(*) as attempts'))
            ->where('status', 'failed')
            ->where('attempted_at', '>=', $cutoff)
            ->groupBy('ip_address')
            ->having('attempts', '>=', $this->thresholds['failed_logins_per_ip'])
            ->get();

        foreach ($suspiciousIps as $ip) {
            // Check if already recorded in last hour
            $exists = SecurityIncident::where('type', SecurityIncident::TYPE_BRUTE_FORCE)
                ->where('ip_address', $ip->ip_address)
                ->where('detected_at', '>=', $cutoff)
                ->exists();

            if (!$exists) {
                $incident = SecurityIncident::record(
                    SecurityIncident::TYPE_BRUTE_FORCE,
                    "Brute force attack detected from IP: {$ip->ip_address} ({$ip->attempts} failed attempts)",
                    $ip->attempts >= 20 ? SecurityIncident::SEVERITY_CRITICAL : SecurityIncident::SEVERITY_HIGH,
                    null,
                    'login',
                    [
                        'ip_address' => $ip->ip_address,
                        'attempts' => $ip->attempts,
                        'timeframe' => '1 hour',
                    ]
                );
                $incidents[] = $incident;
            }
        }

        return $incidents;
    }

    /**
     * Detect distributed attack (many IPs targeting same users)
     */
    public function detectDistributedAttack(): ?SecurityIncident
    {
        $cutoff = now()->subMinutes(30);

        // Check for many different IPs failing on same username
        $targetedUsers = LoginAttempt::select(
                'username',
                DB::raw('COUNT(DISTINCT ip_address) as unique_ips'),
                DB::raw('COUNT(*) as attempts')
            )
            ->where('status', 'failed')
            ->where('attempted_at', '>=', $cutoff)
            ->groupBy('username')
            ->having('unique_ips', '>=', 5)
            ->having('attempts', '>=', 10)
            ->first();

        if ($targetedUsers) {
            // Check if already recorded
            $exists = SecurityIncident::where('type', 'distributed_attack')
                ->where('detected_at', '>=', $cutoff)
                ->where('metadata', 'like', "%{$targetedUsers->username}%")
                ->exists();

            if (!$exists) {
                return SecurityIncident::record(
                    'distributed_attack',
                    "Distributed attack detected targeting: {$targetedUsers->username}",
                    SecurityIncident::SEVERITY_CRITICAL,
                    null,
                    'login',
                    [
                        'target_username' => $targetedUsers->username,
                        'unique_ips' => $targetedUsers->unique_ips,
                        'total_attempts' => $targetedUsers->attempts,
                        'timeframe' => '30 minutes',
                    ]
                );
            }
        }

        return null;
    }

    /**
     * Detect potential account compromise
     */
    public function detectAccountCompromise(): array
    {
        $incidents = [];
        $cutoff = now()->subHours(24);

        // Look for successful logins from new IPs after failed attempts
        $users = User::all();

        foreach ($users as $user) {
            // Get user's known IPs (successful logins in last 30 days)
            $knownIps = LoginAttempt::where('user_id', $user->id)
                ->where('status', 'success')
                ->where('attempted_at', '>=', now()->subDays(30))
                ->distinct()
                ->pluck('ip_address')
                ->toArray();

            if (empty($knownIps)) {
                continue;
            }

            // Check for successful logins from unknown IPs in last 24h
            $suspiciousLogins = LoginAttempt::where('user_id', $user->id)
                ->where('status', 'success')
                ->where('attempted_at', '>=', $cutoff)
                ->whereNotIn('ip_address', $knownIps)
                ->get();

            foreach ($suspiciousLogins as $login) {
                // Check if this IP had failed attempts before succeeding
                $failedBefore = LoginAttempt::where('username', $user->email)
                    ->where('ip_address', $login->ip_address)
                    ->where('status', 'failed')
                    ->where('attempted_at', '<', $login->attempted_at)
                    ->where('attempted_at', '>=', $cutoff)
                    ->exists();

                if ($failedBefore) {
                    $exists = SecurityIncident::where('type', 'account_compromise')
                        ->where('user_id', $user->id)
                        ->where('detected_at', '>=', $cutoff)
                        ->exists();

                    if (!$exists) {
                        $incidents[] = SecurityIncident::record(
                            'account_compromise',
                            "Potential account compromise: Login from new IP after failed attempts",
                            SecurityIncident::SEVERITY_HIGH,
                            $user->id,
                            'user_account',
                            [
                                'new_ip' => $login->ip_address,
                                'known_ips' => $knownIps,
                                'login_time' => $login->attempted_at->toIso8601String(),
                            ]
                        );
                    }
                }
            }
        }

        return $incidents;
    }

    /**
     * Detect unusual activity patterns
     */
    public function detectUnusualActivity(): array
    {
        $incidents = [];
        $cutoff = now()->subHour();

        // Check for unusual login times (outside business hours + weekends)
        $hour = (int) now()->format('H');
        $isWeekend = now()->isWeekend();

        if ($hour < 6 || $hour > 22 || $isWeekend) {
            $offHoursLogins = LoginAttempt::where('status', 'success')
                ->where('attempted_at', '>=', $cutoff)
                ->whereNotNull('user_id')
                ->get();

            foreach ($offHoursLogins as $login) {
                // Only flag if this is unusual for this user
                $user = User::find($login->user_id);
                if ($user && $user->role === 'admin') {
                    $exists = SecurityIncident::where('type', 'unusual_activity')
                        ->where('user_id', $login->user_id)
                        ->where('detected_at', '>=', $cutoff)
                        ->exists();

                    if (!$exists) {
                        $incidents[] = SecurityIncident::record(
                            'unusual_activity',
                            "Admin login during unusual hours",
                            SecurityIncident::SEVERITY_LOW,
                            $login->user_id,
                            'login',
                            [
                                'login_time' => $login->attempted_at->toIso8601String(),
                                'ip_address' => $login->ip_address,
                                'is_weekend' => $isWeekend,
                                'hour' => $hour,
                            ]
                        );
                    }
                }
            }
        }

        return $incidents;
    }

    /**
     * Check if an IP is blocked
     */
    public function isIpBlocked(string $ip): bool
    {
        $blocklist = cache()->get('security_ip_blocklist', []);
        
        if (!isset($blocklist[$ip])) {
            return false;
        }

        $block = $blocklist[$ip];
        return Carbon::parse($block['expires_at'])->isFuture();
    }

    /**
     * Get threat level based on recent incidents
     */
    public function getThreatLevel(): string
    {
        $recentCritical = SecurityIncident::where('severity', 'critical')
            ->where('detected_at', '>=', now()->subHours(24))
            ->whereIn('status', ['open', 'investigating', null])
            ->count();

        $recentHigh = SecurityIncident::where('severity', 'high')
            ->where('detected_at', '>=', now()->subHours(24))
            ->whereIn('status', ['open', 'investigating', null])
            ->count();

        if ($recentCritical >= 3 || $recentHigh >= 10) {
            return 'critical';
        } elseif ($recentCritical >= 1 || $recentHigh >= 5) {
            return 'high';
        } elseif ($recentHigh >= 1) {
            return 'elevated';
        }

        return 'normal';
    }
}
