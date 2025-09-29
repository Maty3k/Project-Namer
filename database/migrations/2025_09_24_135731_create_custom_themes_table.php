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
        Schema::create('custom_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('theme_name', 100);
            $table->string('primary_color', 7);
            $table->string('accent_color', 7)->nullable();
            $table->string('background_color', 7);
            $table->string('text_color', 7);
            $table->boolean('is_dark_mode')->default(false);
            $table->boolean('is_imported')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'theme_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_themes');
    }
};
