<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\IncidentDetectionService;
use App\Models\SystemLog;

class DetectIncidents extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'incidents:detect 
                            {--detailed : Show detailed output}';

    /**
     * The console command description.
     */
    protected $description = 'Run incident detection to identify security threats';

    /**
     * Execute the console command.
     */
    public function handle(IncidentDetectionService $detector): int
    {
        $this->info('Running incident detection...');
        $this->newLine();

        try {
            $incidents = $detector->runDetection();

            $totalDetected = 0;
            foreach ($incidents as $type => $detected) {
                if (is_array($detected)) {
                    $count = count($detected);
                } else {
                    $count = $detected ? 1 : 0;
                }
                $totalDetected += $count;

                if ($count > 0) {
                    $this->warn("Detected {$count} {$type} incident(s)");
                    
                    if ($this->option('detailed') && is_array($detected)) {
                        foreach ($detected as $incident) {
                            $this->line("  - ID #{$incident->id}: {$incident->description}");
                        }
                    }
                }
            }

            $this->newLine();
            if ($totalDetected > 0) {
                $this->warn("Total incidents detected: {$totalDetected}");
                
                SystemLog::security("Incident detection completed", 'incident_detection', [
                    'incidents_detected' => $totalDetected,
                    'types' => array_keys($incidents),
                ]);
            } else {
                $this->info('No new incidents detected.');
            }

            // Get current threat level
            $threatLevel = $detector->getThreatLevel();
            $this->info("Current threat level: " . strtoupper($threatLevel));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Detection failed: {$e->getMessage()}");
            
            SystemLog::error("Incident detection failed", 'incident_detection_error', [
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }
}
