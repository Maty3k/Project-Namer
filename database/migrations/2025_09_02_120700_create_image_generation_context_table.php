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
        Schema::create('image_generation_context', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_image_id')->constrained()->cascadeOnDelete();
            $table->foreignId('generation_session_id')->constrained()->cascadeOnDelete();
            $table->enum('generation_type', ['name', 'logo']);

            // AI Analysis
            $table->json('vision_analysis')->nullable();
            $table->decimal('influence_score', 3, 2)->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index(['generation_session_id']);
            $table->index(['project_image_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_generation_context');
    }
};
