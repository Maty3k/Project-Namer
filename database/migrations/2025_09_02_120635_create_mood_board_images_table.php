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
        Schema::create('mood_board_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mood_board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_image_id')->constrained()->cascadeOnDelete();

            // Positioning
            $table->integer('position')->default(0);
            $table->integer('x_position')->nullable();
            $table->integer('y_position')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('z_index')->default(0);

            // Metadata
            $table->text('notes')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index(['mood_board_id']);
            $table->index(['position']);
            $table->unique(['mood_board_id', 'project_image_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mood_board_images');
    }
};
