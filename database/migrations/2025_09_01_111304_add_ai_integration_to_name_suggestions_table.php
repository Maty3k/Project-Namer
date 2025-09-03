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
        Schema::table('name_suggestions', function (Blueprint $table) {
            $table->string('ai_model_used')->nullable()->after('generation_metadata');
            $table->string('ai_generation_mode')->nullable()->after('ai_model_used');
            $table->boolean('ai_deep_thinking')->default(false)->after('ai_generation_mode');
            $table->integer('ai_response_time_ms')->nullable()->after('ai_deep_thinking');
            $table->integer('ai_tokens_used')->nullable()->after('ai_response_time_ms');
            $table->integer('ai_cost_cents')->nullable()->after('ai_tokens_used');
            $table->string('ai_generation_session_id')->nullable()->after('ai_cost_cents');
            $table->json('ai_prompt_metadata')->nullable()->after('ai_generation_session_id');

            $table->index(['ai_model_used', 'created_at']);
            $table->index(['ai_generation_session_id']);
            $table->index(['ai_generation_mode', 'ai_deep_thinking']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('name_suggestions', function (Blueprint $table) {
            $table->dropIndex(['ai_model_used', 'created_at']);
            $table->dropIndex(['ai_generation_session_id']);
            $table->dropIndex(['ai_generation_mode', 'ai_deep_thinking']);

            $table->dropColumn([
                'ai_model_used',
                'ai_generation_mode',
                'ai_deep_thinking',
                'ai_response_time_ms',
                'ai_tokens_used',
                'ai_cost_cents',
                'ai_generation_session_id',
                'ai_prompt_metadata',
            ]);
        });
    }
};
