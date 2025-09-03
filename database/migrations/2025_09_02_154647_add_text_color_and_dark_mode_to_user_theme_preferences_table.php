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
        Schema::table('user_theme_preferences', function (Blueprint $table) {
            $table->string('text_color', 7)->after('text_secondary_color')->default('#1F2937');
            $table->boolean('is_dark_mode')->after('theme_config')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_theme_preferences', function (Blueprint $table) {
            $table->dropColumn(['text_color', 'is_dark_mode']);
        });
    }
};
