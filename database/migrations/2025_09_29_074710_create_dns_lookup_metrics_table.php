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
        Schema::create('dns_lookup_metrics', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id');
            $table->unsignedInteger('domains_checked')->default(0);
            $table->unsignedInteger('successful_lookups')->default(0);
            $table->unsignedInteger('failed_lookups')->default(0);
            $table->unsignedInteger('cache_hits')->default(0);
            $table->decimal('average_lookup_time', 8, 3)->nullable();
            $table->decimal('total_processing_time', 8, 3);
            $table->timestamp('started_at');
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->index('batch_id', 'idx_batch_id');
            $table->index('completed_at', 'idx_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dns_lookup_metrics');
    }
};
