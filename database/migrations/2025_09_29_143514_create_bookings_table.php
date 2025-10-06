<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->string('booking_id', 12)->primary();        // e.g. BKG0001
            $table->string('customer_name', 150);
            $table->string('email', 150);
            $table->string('contact_number', 60);
            $table->string('service_type', 120);
            $table->date('preferred_date');
            $table->string('preferred_time', 20);               // store selected slot (e.g. "14:30")
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('pending');   // pending | approved | rejected (later)
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['preferred_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};