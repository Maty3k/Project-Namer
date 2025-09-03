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
        // Add indexes to ai_generations table
        Schema::table('ai_generations', function (Blueprint $table) {
            // Index for user queries
            if (! Schema::hasIndex('ai_generations', 'ai_generations_user_id_created_at_index')) {
                $table->index(['user_id', 'created_at'], 'ai_generations_user_id_created_at_index');
            }

            // Index for project queries
            if (! Schema::hasIndex('ai_generations', 'ai_generations_project_id_status_index')) {
                $table->index(['project_id', 'status'], 'ai_generations_project_id_status_index');
            }

            // Index for session queries
            if (! Schema::hasIndex('ai_generations', 'ai_generations_generation_session_id_index')) {
                $table->index('generation_session_id', 'ai_generations_generation_session_id_index');
            }

            // Index for status filtering
            if (! Schema::hasIndex('ai_generations', 'ai_generations_status_created_at_index')) {
                $table->index(['status', 'created_at'], 'ai_generations_status_created_at_index');
            }
        });

        // Add indexes to generation_sessions table
        Schema::table('generation_sessions', function (Blueprint $table) {
            // Index for user queries with status
            if (! Schema::hasIndex('generation_sessions', 'generation_sessions_user_id_status_index')) {
                $table->index(['user_id', 'status'], 'generation_sessions_user_id_status_index');
            }

            // Index for project queries
            if (! Schema::hasIndex('generation_sessions', 'generation_sessions_project_id_created_at_index')) {
                $table->index(['project_id', 'created_at'], 'generation_sessions_project_id_created_at_index');
            }

            // Index for status queries with dates
            if (! Schema::hasIndex('generation_sessions', 'generation_sessions_status_completed_at_index')) {
                $table->index(['status', 'completed_at'], 'generation_sessions_status_completed_at_index');
            }
        });

        // Add indexes to name_suggestions table
        Schema::table('name_suggestions', function (Blueprint $table) {
            // Index for project queries with visibility
            if (! Schema::hasIndex('name_suggestions', 'name_suggestions_project_id_is_hidden_index')) {
                $table->index(['project_id', 'is_hidden'], 'name_suggestions_project_id_is_hidden_index');
            }

            // Index for AI generation relationship
            if (! Schema::hasIndex('name_suggestions', 'name_suggestions_ai_generation_id_index')) {
                $table->index('ai_generation_id', 'name_suggestions_ai_generation_id_index');
            }

            // Index for session relationship
            if (! Schema::hasIndex('name_suggestions', 'name_suggestions_generation_session_id_index')) {
                $table->index('generation_session_id', 'name_suggestions_generation_session_id_index');
            }

            // Index for searching by name
            if (! Schema::hasIndex('name_suggestions', 'name_suggestions_name_index')) {
                $table->index('name', 'name_suggestions_name_index');
            }
        });

        // Add indexes to ai_model_performance table
        Schema::table('ai_model_performance', function (Blueprint $table) {
            // Index for model performance queries (removing conflicting unique constraint)
            if (! Schema::hasIndex('ai_model_performance', 'ai_model_performance_model_name_index')) {
                $table->index('model_name', 'ai_model_performance_model_name_index');
            }

            // Index for last used queries
            if (! Schema::hasIndex('ai_model_performance', 'ai_model_performance_last_used_at_index')) {
                $table->index('last_used_at', 'ai_model_performance_last_used_at_index');
            }
        });

        // Add indexes to user_ai_preferences table
        Schema::table('user_ai_preferences', function (Blueprint $table) {
            // Index for user lookups
            if (! Schema::hasIndex('user_ai_preferences', 'user_ai_preferences_user_id_index')) {
                $table->unique('user_id', 'user_ai_preferences_user_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from ai_generations table
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->dropIndex('ai_generations_user_id_created_at_index');
            $table->dropIndex('ai_generations_project_id_status_index');
            $table->dropIndex('ai_generations_generation_session_id_index');
            $table->dropIndex('ai_generations_status_created_at_index');
        });

        // Remove indexes from generation_sessions table
        Schema::table('generation_sessions', function (Blueprint $table) {
            $table->dropIndex('generation_sessions_user_id_status_index');
            $table->dropIndex('generation_sessions_project_id_created_at_index');
            $table->dropIndex('generation_sessions_status_completed_at_index');
        });

        // Remove indexes from name_suggestions table
        Schema::table('name_suggestions', function (Blueprint $table) {
            $table->dropIndex('name_suggestions_project_id_is_hidden_index');
            $table->dropIndex('name_suggestions_ai_generation_id_index');
            $table->dropIndex('name_suggestions_generation_session_id_index');
            $table->dropIndex('name_suggestions_name_index');
        });

        // Remove indexes from ai_model_performance table
        Schema::table('ai_model_performance', function (Blueprint $table) {
            $table->dropIndex('ai_model_performance_model_name_index');
            $table->dropIndex('ai_model_performance_last_used_at_index');
        });

        // Remove indexes from user_ai_preferences table
        Schema::table('user_ai_preferences', function (Blueprint $table) {
            $table->dropIndex('user_ai_preferences_user_id_index');
        });
    }
};
