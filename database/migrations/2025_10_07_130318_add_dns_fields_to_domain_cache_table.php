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
        Schema::table('domain_cache', function (Blueprint $table) {
            $table->boolean('has_dns_records')->nullable()->after('available');
            $table->string('check_method', 20)->default('api')->after('has_dns_records');
            $table->json('dns_records')->nullable()->after('check_method');

            // Add index for efficient queries
            $table->index(['domain', 'check_method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domain_cache', function (Blueprint $table) {
            $table->dropIndex(['domain', 'check_method']);
            $table->dropColumn(['has_dns_records', 'check_method', 'dns_records']);
        });
    }
};
