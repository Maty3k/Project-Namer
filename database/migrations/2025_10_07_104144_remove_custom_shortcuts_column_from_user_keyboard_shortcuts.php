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
        // SQLite doesn't support dropping columns easily, so we'll recreate the table
        Schema::dropIfExists('user_keyboard_shortcuts');

        Schema::create('user_keyboard_shortcuts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->json('disabled_shortcuts')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_keyboard_shortcuts');

        Schema::create('user_keyboard_shortcuts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->json('custom_shortcuts')->nullable();
            $table->json('disabled_shortcuts')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }
};
