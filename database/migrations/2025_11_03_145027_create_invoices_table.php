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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->constrained()->onDelete('restrict');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            // Invoice identification
            $table->string('invoice_number')->unique();
            $table->string('reference_number')->nullable(); // For Swiss QR reference

            // Dates
            $table->date('invoice_date');
            $table->date('due_date');
            $table->date('paid_at')->nullable();

            // Amounts (stored in smallest currency unit - cents/rappen)
            $table->unsignedBigInteger('subtotal_amount')->default(0); // Subtotal before tax
            $table->unsignedBigInteger('tax_amount')->default(0); // Total tax amount
            $table->unsignedBigInteger('total_amount')->default(0); // Final total
            $table->unsignedBigInteger('paid_amount')->default(0); // Amount already paid
            $table->string('currency', 3)->default('CHF');

            // Tax settings
            $table->decimal('tax_rate', 5, 2)->default(0); // Default tax rate (e.g., 8.1)
            $table->boolean('tax_inclusive')->default(false); // Is tax included in prices?

            // Payment information
            $table->enum('status', ['draft', 'sent', 'viewed', 'partial', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->enum('payment_method', ['bank_transfer', 'cash', 'card', 'other'])->nullable();
            $table->string('payment_reference')->nullable();

            // Swiss QR invoice specific
            $table->string('qr_reference')->nullable(); // Swiss QR reference (27 digits)
            $table->text('qr_additional_info')->nullable(); // Additional structured info for QR
            $table->string('qr_iban')->nullable(); // IBAN for this specific invoice (can override company IBAN)

            // Additional information
            $table->text('notes')->nullable(); // Internal notes
            $table->text('terms')->nullable(); // Payment terms and conditions
            $table->text('footer')->nullable(); // Footer text for invoice

            // File attachments
            $table->string('pdf_path')->nullable(); // Path to generated PDF
            $table->string('qr_code_path')->nullable(); // Path to QR code image

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'invoice_date']);
            $table->index(['company_id', 'contact_id']);
            $table->index('due_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
