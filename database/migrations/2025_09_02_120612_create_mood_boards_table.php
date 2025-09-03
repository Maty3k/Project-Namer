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
        Schema::create('mood_boards', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Board information
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('layout_type', ['grid', 'collage', 'masonry', 'freeform'])->default('grid');
            $table->json('layout_config')->nullable();

            // Sharing
            $table->boolean('is_public')->default(false);
            $table->string('share_token')->unique()->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['project_id']);
            $table->index(['user_id']);
            $table->index(['share_token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mood_boards');
    }
};
