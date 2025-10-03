<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('event_type', 80)->index();                 // e.g. supplier.created, booking.appointed
            $table->string('subject_type', 120)->nullable()->index();  // Fully qualified model class
            $table->string('subject_id', 64)->nullable()->index();     // String to allow non-incrementing PKs
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 255)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['subject_type','subject_id']);
            $table->index(['event_type','occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};