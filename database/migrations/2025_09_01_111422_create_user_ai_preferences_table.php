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
        Schema::create('user_ai_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->json('preferred_models'); // ['gpt-4o', 'claude-3.5-sonnet']
            $table->string('default_generation_mode')->default('creative'); // creative, professional, brandable, tech-focused
            $table->boolean('default_deep_thinking')->default(false);
            $table->json('model_priorities')->nullable(); // {'gpt-4o': 1, 'claude-3.5-sonnet': 2}
            $table->json('custom_parameters')->nullable(); // {'temperature': 0.8, 'max_tokens': 1000}
            $table->json('notification_settings')->nullable(); // {'email_on_completion': true}
            $table->boolean('auto_select_best_model')->default(true);
            $table->boolean('enable_model_comparison')->default(true);
            $table->integer('max_concurrent_generations')->default(3);
            $table->timestamps();

            $table->index(['default_generation_mode']);
            $table->index(['default_deep_thinking']);
            $table->index(['auto_select_best_model']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_ai_preferences');
    }
};
