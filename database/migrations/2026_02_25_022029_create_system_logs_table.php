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
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 50);           // security, audit, error, info
            $table->string('level', 20);             // emergency, alert, critical, error, warning, notice, info, debug
            $table->string('action', 100)->nullable(); // e.g., user.login, employee.created, item.updated
            $table->text('message');
            $table->json('context')->nullable();     // Additional context data
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('method', 10)->nullable(); // GET, POST, PUT, DELETE
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();
            
            // Indexes for efficient querying
            $table->index(['channel', 'logged_at']);
            $table->index(['level', 'logged_at']);
            $table->index(['user_id', 'logged_at']);
            $table->index(['action', 'logged_at']);
            $table->index('logged_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
