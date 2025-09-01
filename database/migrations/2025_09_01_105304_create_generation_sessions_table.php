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
        Schema::create('generation_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->text('business_description');
            $table->string('generation_mode'); // creative, professional, brandable, tech-focused
            $table->boolean('deep_thinking')->default(false);
            $table->json('requested_models');
            $table->json('custom_parameters')->nullable();
            $table->json('results')->nullable();
            $table->json('execution_metadata')->nullable();
            $table->integer('progress_percentage')->default(0);
            $table->string('current_step')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->string('generation_strategy')->default('parallel'); // parallel, quick, comprehensive, custom
            $table->timestamps();

            $table->index(['session_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generation_sessions');
    }
};
