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
        Schema::create('share_accesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('share_id')->constrained('shares')->onDelete('cascade');
            $table->string('ip_address', 45)->nullable(); // Support IPv6
            $table->text('user_agent')->nullable();
            $table->string('referrer', 500)->nullable();
            $table->timestamp('accessed_at')->useCurrent();

            // Indexes for analytics and performance
            $table->index('share_id');
            $table->index('accessed_at');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('share_accesses');
    }
};
