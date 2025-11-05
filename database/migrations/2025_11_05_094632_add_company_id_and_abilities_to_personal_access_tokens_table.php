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
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Add company_id to scope tokens to specific companies
            $table->foreignId('company_id')
                ->nullable()
                ->after('tokenable_id')
                ->constrained('companies')
                ->onDelete('cascade');

            // Index for faster lookups
            $table->index(['tokenable_type', 'tokenable_id', 'company_id'], 'token_company_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropIndex('token_company_idx');
            $table->dropColumn('company_id');
        });
    }
};
