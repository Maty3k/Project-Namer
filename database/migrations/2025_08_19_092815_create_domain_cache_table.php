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
        Schema::create('domain_cache', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique()->comment('The domain name (e.g., example.com)');
            $table->boolean('available')->comment('Whether the domain is available for registration');
            $table->timestamp('checked_at')->comment('When the domain availability was last checked');
            $table->timestamps();

            // Indexes for performance
            $table->index(['domain', 'checked_at']);
            $table->index('checked_at'); // For cleanup of expired entries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_cache');
    }
};
