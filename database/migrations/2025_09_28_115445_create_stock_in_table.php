<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_in', function (Blueprint $table) {
            // Make the custom string ID the primary key (instead of just unique)
            $table->string('stockin_id', 10)->primary();

            $table->string('item_id');      // FK to items.item_id (string PK)
            $table->string('supplier_id');  // FK to suppliers.supplier_id (string PK)

            $table->unsignedInteger('quantity');
            $table->decimal('price', 10, 2);       // unit price
            $table->decimal('total_price', 10, 2); // price * quantity
            $table->date('stockin_date');
            $table->timestamps();

            // Indexes (FK columns)
            $table->index('item_id');
            $table->index('supplier_id');

            // FKs
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