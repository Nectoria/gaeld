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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('registration_number')->nullable();

            // Address fields
            $table->string('street')->nullable();
            $table->string('street_number')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->default('CH'); // ISO 3166-1 alpha-2

            // Contact information
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();

            // Banking information for Swiss QR invoices
            $table->string('iban')->nullable();
            $table->string('bank_name')->nullable();

            // Branding
            $table->string('logo_path')->nullable();
            $table->string('primary_color')->nullable();

            // Settings
            $table->string('currency', 3)->default('CHF');
            $table->string('locale', 5)->default('de_CH');
            $table->string('timezone')->default('Europe/Zurich');

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
