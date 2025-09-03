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
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('generation_session_id');
            $table->json('models_requested');
            $table->string('generation_mode'); // creative, professional, brandable, tech-focused
            $table->boolean('deep_thinking')->default(false);
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->text('prompt_used');
            $table->json('results_data')->nullable();
            $table->json('execution_metadata')->nullable();
            $table->integer('total_names_generated')->default(0);
            $table->integer('total_response_time_ms')->nullable();
            $table->integer('total_tokens_used')->nullable();
            $table->integer('total_cost_cents')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['generation_session_id']);
            $table->index(['project_id', 'status', 'created_at']);
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['status', 'started_at']);
            $table->index(['generation_mode', 'deep_thinking']);
            $table->index(['completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
    }
};
