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
        Schema::create('uploaded_logos', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('original_name', 500);
            $table->string('file_path', 500);
            $table->integer('file_size');
            $table->string('mime_type', 50);
            $table->integer('image_width')->nullable();
            $table->integer('image_height')->nullable();
            $table->string('category', 50)->nullable()->index(); // user-defined category
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploaded_logos');
    }
};
