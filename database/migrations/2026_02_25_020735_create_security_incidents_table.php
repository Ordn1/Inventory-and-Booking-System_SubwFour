<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('security_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 50);  // brute_force, suspicious_input, rate_limit, unauthorized_access, etc.
            $table->string('severity', 20)->default('medium');  // low, medium, high, critical
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('target_resource', 255)->nullable();  // The resource being accessed
            $table->text('description');
            $table->json('metadata')->nullable();  // Additional context data
            $table->enum('status', ['open', 'investigating', 'resolved', 'dismissed'])->default('open');
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('detected_at')->useCurrent();
            $table->timestamps();
            
            // Indexes for efficient querying
            $table->index(['type', 'detected_at']);
            $table->index(['severity', 'status']);
            $table->index(['ip_address', 'detected_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_incidents');
    }
};
