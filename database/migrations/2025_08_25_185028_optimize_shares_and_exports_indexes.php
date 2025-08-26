<?php

declare(strict_types=1);

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
        // Optimize shares table indexes
        Schema::table('shares', function (Blueprint $table): void {
            // Composite index for active shares filtering
            $table->index(['is_active', 'expires_at'], 'shares_active_expires_idx');

            // Index for user shares with date ordering
            $table->index(['user_id', 'created_at'], 'shares_user_created_idx');

            // Index for share type filtering
            $table->index('share_type', 'shares_type_idx');

            // Index for frequently accessed shares by status
            $table->index(['is_active', 'share_type', 'created_at'], 'shares_status_type_date_idx');

            // Index for share search functionality
            $table->index(['user_id', 'is_active'], 'shares_user_active_idx');
        });

        // Optimize share_accesses table indexes
        Schema::table('share_accesses', function (Blueprint $table): void {
            // Composite index for analytics queries
            $table->index(['share_id', 'accessed_at'], 'share_accesses_share_date_idx');

            // Index for IP-based analytics
            $table->index(['share_id', 'ip_address'], 'share_accesses_share_ip_idx');

            // Index for date-based analytics
            $table->index('accessed_at', 'share_accesses_date_idx');

            // Index for referrer analytics
            $table->index(['share_id', 'referrer'], 'share_accesses_share_referrer_idx');
        });

        // Optimize exports table indexes
        Schema::table('exports', function (Blueprint $table): void {
            // Composite index for user exports with status
            $table->index(['user_id', 'expires_at'], 'exports_user_expires_idx');

            // Index for export type filtering
            $table->index('export_type', 'exports_type_idx');

            // Index for cleanup operations (expired exports)
            $table->index('expires_at', 'exports_expires_idx');

            // Composite index for export management
            $table->index(['user_id', 'export_type', 'created_at'], 'exports_user_type_date_idx');

            // Index for exportable polymorphic relationships
            $table->index(['exportable_type', 'exportable_id'], 'exports_morphed_idx');
        });

        // Add indexes to logo_generations table for better performance
        Schema::table('logo_generations', function (Blueprint $table): void {
            // Index for user-based queries with status
            $table->index(['user_id', 'status'], 'logo_generations_user_status_idx');

            // Index for completion status filtering
            $table->index('status', 'logo_generations_status_idx');

            // Index for date-based queries
            $table->index(['user_id', 'created_at'], 'logo_generations_user_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop shares table indexes
        Schema::table('shares', function (Blueprint $table): void {
            $table->dropIndex('shares_active_expires_idx');
            $table->dropIndex('shares_user_created_idx');
            $table->dropIndex('shares_type_idx');
            $table->dropIndex('shares_status_type_date_idx');
            $table->dropIndex('shares_user_active_idx');
        });

        // Drop share_accesses table indexes
        Schema::table('share_accesses', function (Blueprint $table): void {
            $table->dropIndex('share_accesses_share_date_idx');
            $table->dropIndex('share_accesses_share_ip_idx');
            $table->dropIndex('share_accesses_date_idx');
            $table->dropIndex('share_accesses_share_referrer_idx');
        });

        // Drop exports table indexes
        Schema::table('exports', function (Blueprint $table): void {
            $table->dropIndex('exports_user_expires_idx');
            $table->dropIndex('exports_type_idx');
            $table->dropIndex('exports_expires_idx');
            $table->dropIndex('exports_user_type_date_idx');
            $table->dropIndex('exports_morphed_idx');
        });

        // Drop logo_generations table indexes
        Schema::table('logo_generations', function (Blueprint $table): void {
            $table->dropIndex('logo_generations_user_status_idx');
            $table->dropIndex('logo_generations_status_idx');
            $table->dropIndex('logo_generations_user_date_idx');
        });
    }
};
