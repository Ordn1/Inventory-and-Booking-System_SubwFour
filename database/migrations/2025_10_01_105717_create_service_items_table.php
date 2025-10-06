<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('service_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->string('item_id', 12);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['service_id','item_id']);

            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
            $table->foreign('item_id')->references('item_id')->on('items')->restrictOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('service_items');
    }
};