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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');

            // Item details
            $table->string('name'); // Product/Service name
            $table->text('description')->nullable();
            $table->string('sku')->nullable(); // Product SKU/Code

            // Quantity and pricing
            $table->decimal('quantity', 12, 4)->default(1); // Support fractional quantities (e.g., 1.5 hours)
            $table->string('unit')->default('pcs'); // Unit: pcs, hours, days, etc.
            $table->unsignedBigInteger('unit_price')->default(0); // Price per unit in smallest currency unit
            $table->unsignedBigInteger('subtotal')->default(0); // Subtotal before tax (quantity * unit_price)

            // Tax
            $table->decimal('tax_rate', 5, 2)->default(0); // Tax rate for this item (e.g., 8.1)
            $table->unsignedBigInteger('tax_amount')->default(0); // Tax amount
            $table->unsignedBigInteger('total')->default(0); // Total including tax

            // Discount
            $table->decimal('discount_percent', 5, 2)->default(0); // Discount percentage
            $table->unsignedBigInteger('discount_amount')->default(0); // Discount amount

            // Ordering
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['invoice_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
