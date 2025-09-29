<?php

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
        Schema::table('name_suggestions', function (Blueprint $table) {
            $table->boolean('dns_checked')->default(false)->after('is_hidden');
            $table->boolean('dns_has_records')->nullable()->after('dns_checked');
            $table->timestamp('dns_checked_at')->nullable()->after('dns_has_records');

            $table->index('dns_checked', 'idx_dns_checked');
            $table->index('dns_has_records', 'idx_dns_has_records');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('name_suggestions', function (Blueprint $table) {
            $table->dropIndex('idx_dns_checked');
            $table->dropIndex('idx_dns_has_records');
            $table->dropColumn(['dns_checked', 'dns_has_records', 'dns_checked_at']);
        });
    }
};
