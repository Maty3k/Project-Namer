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
        Schema::create('user_theme_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // Theme configuration
            $table->string('theme_name', 100)->default('default');
            $table->boolean('is_custom_theme')->default(false);

            // Color scheme
            $table->string('primary_color', 7)->default('#3B82F6'); // Blue-500
            $table->string('secondary_color', 7)->default('#8B5CF6'); // Purple-500
            $table->string('accent_color', 7)->default('#10B981'); // Green-500
            $table->string('background_color', 7)->default('#FFFFFF');
            $table->string('surface_color', 7)->default('#F8FAFC');
            $table->string('text_primary_color', 7)->default('#1F2937');
            $table->string('text_secondary_color', 7)->default('#6B7280');

            // Dark mode variants
            $table->string('dark_background_color', 7)->default('#111827');
            $table->string('dark_surface_color', 7)->default('#1F2937');
            $table->string('dark_text_primary_color', 7)->default('#F9FAFB');
            $table->string('dark_text_secondary_color', 7)->default('#D1D5DB');

            // UI preferences
            $table->enum('border_radius', ['none', 'small', 'medium', 'large', 'full'])->default('medium');
            $table->enum('font_size', ['small', 'medium', 'large'])->default('medium');
            $table->boolean('compact_mode')->default(false);

            // Theme metadata
            $table->json('theme_config')->nullable(); // Store complete CSS custom properties

            $table->timestamps();

            // Indexes
            $table->index(['theme_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_theme_preferences');
    }
};
