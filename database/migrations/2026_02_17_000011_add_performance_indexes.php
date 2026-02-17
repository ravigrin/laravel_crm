<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds missing indexes for performance optimization.
     * It should be run in production to improve query performance.
     */
    public function up(): void
    {
        // Only add indexes if they don't exist
        // This is safer than dropping and recreating
        
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                // Email field for searching
                $this->addIndexIfNotExists($table, 'email', 'leads_email_idx');
                
                // Phone field for duplicate detection
                $this->addIndexIfNotExists($table, 'phone', 'leads_phone_idx');
                
                // Composite indexes for common queries
                $this->addCompositeIndexIfNotExists('leads', ['status', 'created_at'], 'leads_status_created_idx');
                $this->addCompositeIndexIfNotExists('leads', ['user_id', 'created_at'], 'leads_user_created_idx');
                $this->addCompositeIndexIfNotExists('leads', ['project_id', 'created_at'], 'leads_project_created_idx');
                $this->addCompositeIndexIfNotExists('leads', ['blocked', 'created_at'], 'leads_blocked_created_idx');
                
                // External ID for integration lookups
                $this->addIndexIfNotExists($table, 'external_id', 'leads_external_id_idx');
            });
        }

        if (Schema::hasTable('integration_credentials')) {
            Schema::table('integration_credentials', function (Blueprint $table) {
                $this->addIndexIfNotExists($table, 'type', 'integration_creds_type_idx');
                $this->addCompositeIndexIfNotExists('integration_credentials', ['user_id', 'type'], 'integration_creds_user_type_idx');
            });
        }

        if (Schema::hasTable('failed_jobs')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                $this->addCompositeIndexIfNotExists('failed_jobs', ['failed_at', 'queue'], 'failed_jobs_failed_at_queue_idx');
            });
        }

        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                $this->addCompositeIndexIfNotExists('jobs', ['queue', 'available_at'], 'jobs_queue_available_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safely drop indexes only if they exist
        $this->dropIndexIfExists('leads', 'leads_email_idx');
        $this->dropIndexIfExists('leads', 'leads_phone_idx');
        $this->dropIndexIfExists('leads', 'leads_status_created_idx');
        $this->dropIndexIfExists('leads', 'leads_user_created_idx');
        $this->dropIndexIfExists('leads', 'leads_project_created_idx');
        $this->dropIndexIfExists('leads', 'leads_blocked_created_idx');
        
        $this->dropIndexIfExists('integration_credentials', 'integration_creds_type_idx');
        $this->dropIndexIfExists('integration_credentials', 'integration_creds_user_type_idx');
        
        $this->dropIndexIfExists('failed_jobs', 'failed_jobs_failed_at_queue_idx');
        $this->dropIndexIfExists('jobs', 'jobs_queue_available_idx');
    }

    /**
     * Helper: Add index if it doesn't exist
     */
    private function addIndexIfNotExists(Blueprint $table, string $column, string $indexName): void
    {
        $indexedColumns = \DB::select(
            "SELECT * FROM pg_indexes WHERE tablename = ? AND indexname = ?",
            [\DB::getTablePrefix() . 'leads', $indexName]
        );

        if (empty($indexedColumns)) {
            $table->index($column, $indexName);
        }
    }

    /**
     * Helper: Add composite index if it doesn't exist
     */
    private function addCompositeIndexIfNotExists(string $table, array $columns, string $indexName): void
    {
        $indexedColumns = \DB::select(
            "SELECT * FROM pg_indexes WHERE tablename = ? AND indexname = ?",
            [$table, $indexName]
        );

        if (empty($indexedColumns)) {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        }
    }

    /**
     * Helper: Drop index if it exists
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $indexedColumns = \DB::select(
            "SELECT * FROM pg_indexes WHERE tablename = ? AND indexname = ?",
            [$table, $indexName]
        );

        if (!empty($indexedColumns)) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
};
