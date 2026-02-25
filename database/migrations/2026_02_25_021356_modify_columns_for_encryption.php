<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Modify columns to TEXT type to support encrypted data storage.
     * Encrypted values are longer than the original plaintext.
     */
    public function up(): void
    {
        // Employees table - SSS number and contact
        Schema::table('employees', function (Blueprint $table) {
            $table->text('sss_number')->change();
            $table->text('contact_number')->change();
        });

        // Suppliers table - contact number
        Schema::table('suppliers', function (Blueprint $table) {
            $table->text('number')->change();
        });

        // Bookings table - customer PII
        Schema::table('bookings', function (Blueprint $table) {
            $table->text('email')->change();
            $table->text('contact_number')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: Reversing will truncate encrypted data
        Schema::table('employees', function (Blueprint $table) {
            $table->string('sss_number', 40)->change();
            $table->string('contact_number', 40)->change();
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('number', 15)->change();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('email', 255)->change();
            $table->string('contact_number', 20)->change();
        });
    }
};
