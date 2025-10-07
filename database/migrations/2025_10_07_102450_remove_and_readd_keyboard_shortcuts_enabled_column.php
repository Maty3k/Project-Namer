<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset all enabled values to true (fresh start)
        DB::table('user_keyboard_shortcuts')->update(['enabled' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to rollback - data changes only
    }
};
