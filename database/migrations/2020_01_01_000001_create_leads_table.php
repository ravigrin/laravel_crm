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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            
            // Ownership fields
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('quiz_id')->nullable();
            
            // External identification
            $table->string('external_id', 150)->unique()->nullable();
            $table->string('external_system');
            $table->string('external_entity');
            $table->string('external_entity_id');
            $table->string('external_project_id')->nullable();
            
            // Contact information
            $table->string('name', 150)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 150)->nullable();
            $table->jsonb('messengers')->nullable();
            $table->jsonb('contacts')->nullable()->comment('Encrypted personal data');
            
            // Location
            $table->string('ip_address', 45)->nullable();
            $table->string('city', 100)->nullable()->comment('City from IP geolocation');
            $table->string('country', 100)->nullable()->comment('Country from IP geolocation');
            
            // UTM tags
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            
            // Lead data
            $table->jsonb('data');
            $table->integer('status')->default(\App\Enums\DefaultStatuses::New);
            $table->string('integration_status')->nullable()->comment('Status of integration processing');
            $table->jsonb('integration_data')->nullable()->comment('Integration response data');
            
            // Lead flags
            $table->boolean('is_test')->default(false)->comment('Test lead flag');
            $table->boolean('viewed')->default(false)->comment('Lead viewed flag');
            $table->boolean('paid')->default(false)->comment('Lead paid flag');
            $table->boolean('blocked')->default(false)->comment('Lead blocked flag');
            
            // Duplicate detection
            $table->string('fingerprint', 255)->nullable()->comment('Client fingerprint for duplicate detection');
            $table->unsignedBigInteger('equal_answer_id')->nullable()->comment('Reference to equal lead');
            
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'created_at'], 'leads_user_created_idx');
            $table->index(['project_id', 'created_at'], 'leads_project_created_idx');
            $table->index(['quiz_id', 'created_at'], 'leads_quiz_created_idx');
            $table->index(['status', 'created_at'], 'leads_status_created_idx');
            $table->index('name', 'leads_name_idx');
            $table->index('phone', 'leads_phone_idx');
            $table->index('external_id', 'leads_external_id_idx');
            $table->index('external_entity_id', 'leads_external_entity_id_idx');
            $table->index('fingerprint', 'leads_fingerprint_idx');
            $table->index('ip_address', 'leads_ip_address_idx');
            $table->index('utm_source', 'leads_utm_source_idx');
            $table->index('utm_medium', 'leads_utm_medium_idx');
            $table->index('utm_campaign', 'leads_utm_campaign_idx');
            $table->index('utm_content', 'leads_utm_content_idx');
            $table->index('utm_term', 'leads_utm_term_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};

