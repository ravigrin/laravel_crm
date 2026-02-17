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
        Schema::create('phone_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('phone', 150);
            $table->string('code', 10)->nullable()->comment('Verification code from greensms');
            $table->enum('status', ['pending', 'verified', 'failed', 'expired'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('attempts')->default(0);
            $table->jsonb('greensms_response')->nullable()->comment('Response from greensms API');
            $table->timestamps();

            $table->index('lead_id', 'phone_verifications_lead_id_idx');
            $table->index('phone', 'phone_verifications_phone_idx');
            $table->index('status', 'phone_verifications_status_idx');
            $table->index('created_at', 'phone_verifications_created_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_verifications');
    }
};

