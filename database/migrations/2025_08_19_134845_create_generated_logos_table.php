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
        Schema::create('generated_logos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logo_generation_id')
                ->constrained('logo_generations')
                ->onDelete('cascade');
            $table->enum('style', ['minimalist', 'modern', 'playful', 'corporate'])
                ->index();
            $table->integer('variation_number')->default(1);
            $table->text('prompt_used');
            $table->string('original_file_path', 500);
            $table->integer('file_size');
            $table->integer('image_width')->default(1024);
            $table->integer('image_height')->default(1024);
            $table->integer('generation_time_ms');
            $table->string('api_image_url', 1000)->nullable();
            $table->timestamps();

            $table->index(['logo_generation_id', 'style']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_logos');
    }
};
