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
        Schema::table('logo_generations', function (Blueprint $table) {
            $table->integer('progress')->nullable()->after('logos_completed');
            $table->timestamp('started_at')->nullable()->after('error_message');
            $table->timestamp('estimated_completion')->nullable()->after('started_at');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'partial'])
                ->default('pending')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logo_generations', function (Blueprint $table) {
            $table->dropColumn(['progress', 'started_at', 'estimated_completion']);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending')
                ->change();
        });
    }
};
