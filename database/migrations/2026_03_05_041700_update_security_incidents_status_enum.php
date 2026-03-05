<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the ENUM to include all status values used in the application
        DB::statement("ALTER TABLE `security_incidents` MODIFY `status` ENUM('open', 'investigating', 'contained', 'resolved', 'false_positive', 'dismissed') NOT NULL DEFAULT 'open'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First update any rows with new statuses to 'resolved' before reverting
        DB::table('security_incidents')
            ->whereIn('status', ['contained', 'false_positive'])
            ->update(['status' => 'resolved']);
            
        DB::statement("ALTER TABLE `security_incidents` MODIFY `status` ENUM('open', 'investigating', 'resolved', 'dismissed') NOT NULL DEFAULT 'open'");
    }
};
