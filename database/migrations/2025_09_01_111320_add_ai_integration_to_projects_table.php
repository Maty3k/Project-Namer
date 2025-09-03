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
        Schema::table('projects', function (Blueprint $table) {
            $table->json('ai_generation_history')->nullable()->after('selected_name_id');
            $table->string('last_ai_generation_mode')->nullable()->after('ai_generation_history');
            $table->boolean('last_ai_deep_thinking')->default(false)->after('last_ai_generation_mode');
            $table->json('preferred_ai_models')->nullable()->after('last_ai_deep_thinking');
            $table->integer('total_ai_generations')->default(0)->after('preferred_ai_models');
            $table->timestamp('last_ai_generation_at')->nullable()->after('total_ai_generations');

            $table->index(['user_id', 'last_ai_generation_at']);
            $table->index(['total_ai_generations', 'created_at']);
            $table->index(['last_ai_generation_mode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'last_ai_generation_at']);
            $table->dropIndex(['total_ai_generations', 'created_at']);
            $table->dropIndex(['last_ai_generation_mode']);

            $table->dropColumn([
                'ai_generation_history',
                'last_ai_generation_mode',
                'last_ai_deep_thinking',
                'preferred_ai_models',
                'total_ai_generations',
                'last_ai_generation_at',
            ]);
        });
    }
};
