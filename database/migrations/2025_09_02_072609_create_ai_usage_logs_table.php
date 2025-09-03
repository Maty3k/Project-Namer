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
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('model_id', 50);
            $table->integer('input_tokens')->unsigned();
            $table->integer('output_tokens')->unsigned();
            $table->integer('total_tokens')->unsigned();
            $table->decimal('cost', 10, 6)->unsigned();
            $table->decimal('response_time', 8, 3)->unsigned(); // in seconds
            $table->boolean('successful')->default(true);
            $table->json('metadata')->nullable(); // For storing additional context
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['model_id', 'created_at']);
            $table->index(['created_at', 'successful']);
            $table->index('cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
