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
        Schema::table('generated_logos', function (Blueprint $table): void {
            $table->boolean('is_selected_for_refinement')->default(false)->after('status');
            $table->boolean('is_refined')->default(false)->after('is_selected_for_refinement');
            $table->string('refined_file_path')->nullable()->after('is_refined');
            $table->string('quality')->default('low')->after('refined_file_path'); // low, high

            $table->index('is_selected_for_refinement');
            $table->index('is_refined');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_logos', function (Blueprint $table): void {
            $table->dropIndex(['is_selected_for_refinement']);
            $table->dropIndex(['is_refined']);
            $table->dropColumn([
                'is_selected_for_refinement',
                'is_refined',
                'refined_file_path',
                'quality',
            ]);
        });
    }
};
