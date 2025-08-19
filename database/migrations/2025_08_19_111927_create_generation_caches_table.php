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
        Schema::create('generation_caches', function (Blueprint $table) {
            $table->id();
            $table->string('input_hash', 64)->unique();
            $table->text('business_description');
            $table->string('mode');
            $table->boolean('deep_thinking')->default(false);
            $table->json('generated_names');
            $table->timestamp('cached_at');
            $table->timestamps();

            $table->index(['input_hash', 'cached_at']);
            $table->index('cached_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generation_caches');
    }
};
