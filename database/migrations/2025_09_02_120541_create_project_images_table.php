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
        Schema::create('project_images', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // File information
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('file_path', 500);
            $table->string('thumbnail_path', 500)->nullable();
            $table->integer('file_size');
            $table->string('mime_type', 100);

            // Image metadata
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->decimal('aspect_ratio', 5, 2)->nullable();
            $table->json('dominant_colors')->nullable();

            // Organization
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('tags')->nullable();

            // AI Analysis
            $table->json('ai_analysis')->nullable();

            // Status
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->boolean('is_public')->default(false);

            $table->timestamps();

            // Indexes
            $table->index(['project_id']);
            $table->index(['user_id']);
            $table->index(['processing_status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_images');
    }
};
