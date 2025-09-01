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
        Schema::create('ai_model_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('model_name'); // gpt-4o, claude-3.5-sonnet, etc.
            $table->integer('total_requests')->default(0);
            $table->integer('successful_requests')->default(0);
            $table->integer('failed_requests')->default(0);
            $table->integer('average_response_time_ms')->default(0);
            $table->bigInteger('total_tokens_used')->default(0);
            $table->integer('total_cost_cents')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->json('performance_metrics')->nullable(); // Additional metrics like cache hits, fallback usage, etc.
            $table->timestamps();

            $table->unique(['user_id', 'model_name']);
            $table->index(['model_name', 'total_requests']);
            $table->index(['user_id', 'last_used_at']);
            $table->index(['last_used_at']);
            $table->index(['average_response_time_ms']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_model_performance');
    }
};
