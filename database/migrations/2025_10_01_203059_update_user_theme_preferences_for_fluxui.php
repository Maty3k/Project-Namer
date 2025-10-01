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
        Schema::table('user_theme_preferences', function (Blueprint $table): void {
            // Add new FluxUI standard variables
            $table->string('accent_content_color', 7)->nullable()->after('accent_color');
            $table->string('accent_foreground_color', 7)->default('#ffffff')->after('accent_content_color');
            $table->string('base_color_shade', 20)->default('zinc')->after('accent_foreground_color');

            // Add dark mode accent variations
            $table->string('dark_accent_color', 7)->nullable()->after('base_color_shade');
            $table->string('dark_accent_content_color', 7)->nullable()->after('dark_accent_color');
            $table->string('dark_accent_foreground_color', 7)->default('#ffffff')->after('dark_accent_content_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_theme_preferences', function (Blueprint $table): void {
            $table->dropColumn([
                'accent_content_color',
                'accent_foreground_color',
                'base_color_shade',
                'dark_accent_color',
                'dark_accent_content_color',
                'dark_accent_foreground_color',
            ]);
        });
    }
};
