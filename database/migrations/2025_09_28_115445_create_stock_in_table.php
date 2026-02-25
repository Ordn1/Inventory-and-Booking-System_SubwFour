<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_in', function (Blueprint $table) {
            $table->string('stockin_id', 10)->primary();

            $table->string('item_id');      
            $table->string('supplier_id');  

            $table->unsignedInteger('quantity');
            $table->decimal('price', 10, 2);       
            $table->decimal('total_price', 10, 2);
            $table->date('stockin_date');
            $table->timestamps();
            $table->softDeletes();

            $table->index('item_id');
            $table->index('supplier_id');

            $table->foreign('item_id')
                  ->references('item_id')->on('items')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();

            $table->foreign('supplier_id')
                  ->references('supplier_id')->on('suppliers')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_in');
    }
};