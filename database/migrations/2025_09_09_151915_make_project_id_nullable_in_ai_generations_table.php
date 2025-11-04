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
        Schema::table('ai_generations', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['project_id']);
        });

        Schema::table('ai_generations', function (Blueprint $table) {
            // Make the column nullable (use unsignedBigInteger to match foreignId type)
            $table->unsignedBigInteger('project_id')->nullable()->change();
        });

        Schema::table('ai_generations', function (Blueprint $table) {
            // Re-add the foreign key constraint with nullable support
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['project_id']);
        });

        Schema::table('ai_generations', function (Blueprint $table) {
            // Make the column not nullable
            $table->unsignedBigInteger('project_id')->nullable(false)->change();
        });

        Schema::table('ai_generations', function (Blueprint $table) {
            // Re-add the original foreign key constraint
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('cascade');
        });
    }
};
