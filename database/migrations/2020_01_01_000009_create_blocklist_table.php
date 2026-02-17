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
        Schema::create('blocklist', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('User ID for whitelist/blacklist');
            $table->unsignedBigInteger('quiz_id')->nullable()->comment('Quiz ID for blocking');
            $table->unsignedBigInteger('lead_id')->nullable()->comment('Lead ID for blacklist');
            $table->string('fingerprint', 255)->nullable()->comment('Fingerprint for blacklist');
            $table->string('ip_address', 45)->nullable()->comment('IP address for blacklist');
            $table->string('email', 150)->nullable()->comment('Email for blacklist');
            $table->string('phone', 150)->nullable()->comment('Phone for blacklist');
            $table->enum('type', ['blacklist', 'whitelist'])->default('blacklist');
            $table->text('reason')->nullable()->comment('Reason for blocking');
            $table->timestamps();

            $table->index(['user_id', 'type'], 'blocklist_user_type_idx');
            $table->index(['quiz_id', 'type'], 'blocklist_quiz_type_idx');
            $table->index('fingerprint', 'blocklist_fingerprint_idx');
            $table->index('ip_address', 'blocklist_ip_address_idx');
            $table->index('email', 'blocklist_email_idx');
            $table->index('phone', 'blocklist_phone_idx');
            $table->index('lead_id', 'blocklist_lead_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocklist');
    }
};

