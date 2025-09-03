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
        Schema::table('users', function (Blueprint $table) {
            $table->string('current_theme', 100)->default('default');
            $table->boolean('prefers_dark_mode')->default(false);
            $table->boolean('theme_auto_switch')->default(true); // Auto dark/light based on system
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['current_theme', 'prefers_dark_mode', 'theme_auto_switch']);
        });
    }
};
