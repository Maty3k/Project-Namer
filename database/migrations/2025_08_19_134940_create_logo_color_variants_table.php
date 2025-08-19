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
        Schema::create('logo_color_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generated_logo_id')
                  ->constrained('generated_logos')
                  ->onDelete('cascade');
            $table->enum('color_scheme', [
                'monochrome', 'ocean_blue', 'forest_green', 'warm_sunset', 
                'royal_purple', 'corporate_navy', 'earthy_tones', 'tech_blue',
                'vibrant_pink', 'charcoal_gold'
            ])->index();
            $table->string('file_path', 500);
            $table->integer('file_size');
            $table->timestamps();

            $table->unique(['generated_logo_id', 'color_scheme']);
            $table->index(['generated_logo_id', 'color_scheme']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logo_color_variants');
    }
};
