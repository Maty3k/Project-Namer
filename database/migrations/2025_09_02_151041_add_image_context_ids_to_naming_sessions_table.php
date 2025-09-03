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
        Schema::table('naming_sessions', function (Blueprint $table) {
            $table->json('image_context_ids')->nullable()->after('deep_thinking');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('naming_sessions', function (Blueprint $table) {
            $table->dropColumn('image_context_ids');
        });
    }
};
