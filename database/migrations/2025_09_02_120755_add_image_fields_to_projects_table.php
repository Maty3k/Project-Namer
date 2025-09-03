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
        Schema::table('projects', function (Blueprint $table) {
            $table->integer('total_images')->default(0);
            $table->bigInteger('storage_used_bytes')->default(0);
            $table->foreignId('default_mood_board_id')->nullable()->constrained('mood_boards')->nullOnDelete();
            $table->boolean('image_upload_enabled')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['default_mood_board_id']);
            $table->dropColumn(['total_images', 'storage_used_bytes', 'default_mood_board_id', 'image_upload_enabled']);
        });
    }
};
