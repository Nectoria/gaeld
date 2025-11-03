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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // Contact type
            $table->enum('type', ['customer', 'vendor', 'both'])->default('customer');

            // Basic information
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('website')->nullable();

            // Tax information
            $table->string('vat_number')->nullable();
            $table->string('tax_id')->nullable();

            // Address fields
            $table->string('street')->nullable();
            $table->string('street_number')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->default('CH'); // ISO 3166-1 alpha-2

            // Banking information (for vendors)
            $table->string('iban')->nullable();
            $table->string('bank_name')->nullable();

            // Additional information
            $table->text('notes')->nullable();
            $table->string('reference_number')->nullable(); // Internal reference

            // Payment terms
            $table->integer('payment_term_days')->default(30); // Net 30, Net 60, etc.
            $table->string('currency', 3)->default('CHF');

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
