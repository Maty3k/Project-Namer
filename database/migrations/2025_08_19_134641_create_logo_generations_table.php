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
        Schema::create('logo_generations', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->string('business_name');
            $table->text('business_description')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                  ->default('pending')
                  ->index();
            $table->integer('total_logos_requested')->default(12);
            $table->integer('logos_completed')->default(0);
            $table->string('api_provider', 50)->default('openai');
            $table->integer('cost_cents')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logo_generations');
    }
};
