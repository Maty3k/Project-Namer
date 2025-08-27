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
        Schema::create('session_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id');
            $table->json('generated_names');
            $table->json('domain_results');
            $table->json('selected_for_logos')->nullable();
            $table->timestamp('generation_timestamp')->useCurrent();
            $table->timestamps();

            $table->foreign('session_id')
                ->references('id')
                ->on('naming_sessions')
                ->cascadeOnDelete();

            $table->index(['session_id', 'generation_timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_results');
    }
};
