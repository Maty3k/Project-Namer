<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First, drop any orphaned indexes that might exist from previous migrations
        // These indexes reference columns that no longer exist and cause SQLite errors
        $orphanedIndexes = [
            'name_suggestions_ai_generation_id_index',
            'name_suggestions_generation_session_id_index',
        ];

        foreach ($orphanedIndexes as $indexName) {
            try {
                DB::statement("DROP INDEX IF EXISTS {$indexName}");
            } catch (\Exception) {
                // Silently continue if index doesn't exist
            }
        }

        // SQLite workaround: Drop columns one at a time
        $columnsToDrop = [
            'is_custom_theme',
            'primary_color',
            'secondary_color',
            'accent_color',
            'background_color',
            'surface_color',
            'text_primary_color',
            'text_secondary_color',
            'dark_background_color',
            'dark_surface_color',
            'dark_text_primary_color',
            'dark_text_secondary_color',
            'text_color',
            'theme_config',
        ];

        foreach ($columnsToDrop as $column) {
            if (Schema::hasColumn('user_theme_preferences', $column)) {
                Schema::table('user_theme_preferences', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }

        // Add composite index for efficient queries
        Schema::table('user_theme_preferences', function (Blueprint $table): void {
            $table->index(['theme_name', 'is_dark_mode']);
        });
    }

    public function down(): void
    {
        // Restore old columns for rollback
        Schema::table('user_theme_preferences', function (Blueprint $table): void {
            $table->boolean('is_custom_theme')->default(false);
            $table->string('primary_color', 7)->default('#3B82F6');
            $table->string('secondary_color', 7)->default('#8B5CF6');
            $table->string('accent_color', 7)->default('#10B981');
            $table->string('background_color', 7)->default('#FFFFFF');
            $table->string('surface_color', 7)->default('#F8FAFC');
            $table->string('text_primary_color', 7)->default('#1F2937');
            $table->string('text_secondary_color', 7)->default('#6B7280');
            $table->string('dark_background_color', 7)->default('#111827');
            $table->string('dark_surface_color', 7)->default('#1F2937');
            $table->string('dark_text_primary_color', 7)->default('#F9FAFB');
            $table->string('dark_text_secondary_color', 7)->default('#D1D5DB');
            $table->string('text_color', 7)->nullable();
            $table->json('theme_config')->nullable();

            $table->dropIndex(['theme_name', 'is_dark_mode']);
        });
    }
};
