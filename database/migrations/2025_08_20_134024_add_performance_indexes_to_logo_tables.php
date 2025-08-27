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
        Schema::table('logo_generations', function (Blueprint $table): void {
            // Index for filtering by status and creation time (for recent completed/failed generations)
            $table->index(['status', 'created_at']);

            // Index for session-based queries with status filtering
            $table->index(['session_id', 'status']);

            // Index for cost tracking queries
            $table->index('cost_cents');
        });

        Schema::table('generated_logos', function (Blueprint $table): void {
            // Index for logo size filtering
            $table->index('file_size');

            // Index for image dimension queries
            $table->index(['image_width', 'image_height']);

            // Index for generation time analysis
            $table->index('generation_time_ms');

            // Composite index for generation, style, and variation number (for ordered results)
            $table->index(['logo_generation_id', 'style', 'variation_number']);

            // Index for creation time ordering within a generation
            $table->index(['logo_generation_id', 'created_at']);
        });

        Schema::table('logo_color_variants', function (Blueprint $table): void {
            // Index for file size filtering on variants
            $table->index('file_size');

            // Index for color scheme statistics
            $table->index(['color_scheme', 'created_at']);

            // Index for finding variants by logo with creation time ordering
            $table->index(['generated_logo_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logo_generations', function (Blueprint $table): void {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['session_id', 'status']);
            $table->dropIndex(['cost_cents']);
        });

        Schema::table('generated_logos', function (Blueprint $table): void {
            $table->dropIndex(['file_size']);
            $table->dropIndex(['image_width', 'image_height']);
            $table->dropIndex(['generation_time_ms']);
            $table->dropIndex(['logo_generation_id', 'style', 'variation_number']);
            $table->dropIndex(['logo_generation_id', 'created_at']);
        });

        Schema::table('logo_color_variants', function (Blueprint $table): void {
            $table->dropIndex(['file_size']);
            $table->dropIndex(['color_scheme', 'created_at']);
            $table->dropIndex(['generated_logo_id', 'created_at']);
        });
    }
};
