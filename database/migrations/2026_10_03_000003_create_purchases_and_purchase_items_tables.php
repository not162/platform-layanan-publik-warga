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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_transaction_id')->nullable()->constrained('finance_transactions')->nullOnDelete();
            $table->string('vendor_name', 150);
            $table->date('purchase_date');
            $table->string('invoice_number', 100)->nullable();
            $table->string('purpose', 255);
            $table->text('notes')->nullable();
            $table->string('receipt_path', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index('purchase_date');
            $table->index('vendor_name');
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->string('item_name', 150);
            $table->string('description', 255)->nullable();
            $table->unsignedInteger('quantity');
            $table->string('unit', 30)->default('pcs');
            $table->unsignedBigInteger('unit_price_idr');
            $table->unsignedBigInteger('subtotal_idr');
            $table->timestamps();

            $table->index(['purchase_id', 'item_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};
