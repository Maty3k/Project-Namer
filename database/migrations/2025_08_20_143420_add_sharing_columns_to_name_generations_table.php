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
            $table->unsignedInteger('share_count')->default(0);
            $table->timestamp('last_shared_at')->nullable();

            // Index for performance
            $table->index('last_shared_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logo_generations', function (Blueprint $table): void {
            $table->dropColumn(['share_count', 'last_shared_at']);
        });
    }
};
