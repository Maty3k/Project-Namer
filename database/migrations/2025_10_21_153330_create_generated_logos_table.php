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
        Schema::create('generated_logos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('logo_generation_id')->constrained()->cascadeOnDelete();
            $table->string('file_path')->nullable(); // Path to the saved PNG file (256x256) - nullable until generated
            $table->string('style'); // minimalist, modern, playful, corporate
            $table->text('prompt')->nullable(); // The prompt used to generate this logo
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['logo_generation_id', 'status']);
            $table->index('style');
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
