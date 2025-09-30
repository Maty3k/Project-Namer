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
        Schema::create('dns_lookup_cache', function (Blueprint $table) {
            $table->id();
            $table->string('domain');
            $table->string('tld', 10);
            $table->boolean('has_records')->default(false);
            $table->json('record_types')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('checked_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['domain', 'tld'], 'unique_domain_tld');
            $table->index('expires_at', 'idx_expires_at');
            $table->index('checked_at', 'idx_checked_at');
            $table->index('has_records', 'idx_has_records');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dns_lookup_cache');
    }
};
