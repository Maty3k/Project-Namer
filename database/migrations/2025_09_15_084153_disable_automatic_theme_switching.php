<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable automatic theme switching for all users to prevent themes from reverting
        // This addresses the issue where themes automatically revert to "Default Blue"
        DB::table('users')->update(['theme_auto_switch' => 0]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-enable automatic theme switching if needed
        DB::table('users')->update(['theme_auto_switch' => 1]);
    }
};
