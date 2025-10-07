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
        Schema::create('user_keyboard_shortcuts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->boolean('enabled')->default(true); // Global enable/disable for all shortcuts
            $table->json('custom_shortcuts')->nullable(); // Custom key bindings: {'commandPalette': 'cmd+k', 'newProject': 'cmd+n', ...}
            $table->json('disabled_shortcuts')->nullable(); // Individual shortcuts to disable: ['newProject', 'generateNames']
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
    }
};
