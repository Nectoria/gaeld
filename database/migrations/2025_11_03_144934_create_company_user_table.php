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
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Role within the company (separate from Spatie permissions)
            $table->string('role')->default('member'); // owner, admin, accountant, member, viewer

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('joined_at')->nullable();

            $table->timestamps();

            // Ensure a user can only be added once per company
            $table->unique(['company_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }
};
