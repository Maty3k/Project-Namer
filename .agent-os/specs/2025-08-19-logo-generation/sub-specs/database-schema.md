# Database Schema

This is the database schema implementation for the spec detailed in @.agent-os/specs/2025-08-19-logo-generation/spec.md

> Created: 2025-08-19
> Version: 1.0.0

## Schema Changes

### New Tables

#### `logo_generations`
```sql
CREATE TABLE logo_generations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    business_name VARCHAR(255) NOT NULL,
    business_description TEXT,
    status ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    total_logos_requested INTEGER NOT NULL DEFAULT 12,
    logos_completed INTEGER NOT NULL DEFAULT 0,
    api_provider VARCHAR(50) NOT NULL DEFAULT 'openai',
    cost_cents INTEGER NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);
```

#### `generated_logos`
```sql
CREATE TABLE generated_logos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    logo_generation_id BIGINT UNSIGNED NOT NULL,
    style ENUM('minimalist', 'modern', 'playful', 'corporate') NOT NULL,
    variation_number INTEGER NOT NULL DEFAULT 1,
    prompt_used TEXT NOT NULL,
    original_file_path VARCHAR(500) NOT NULL,
    file_size INTEGER NOT NULL,
    image_width INTEGER NOT NULL DEFAULT 1024,
    image_height INTEGER NOT NULL DEFAULT 1024,
    generation_time_ms INTEGER NOT NULL,
    api_image_url VARCHAR(1000),
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (logo_generation_id) REFERENCES logo_generations(id) ON DELETE CASCADE,
    INDEX idx_logo_generation_style (logo_generation_id, style),
    INDEX idx_style (style)
);
```

#### `logo_color_variants`
```sql
CREATE TABLE logo_color_variants (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    generated_logo_id BIGINT UNSIGNED NOT NULL,
    color_scheme ENUM('monochrome', 'ocean_blue', 'forest_green', 'warm_sunset', 'royal_purple', 'corporate_navy', 'earthy_tones', 'tech_blue', 'vibrant_pink', 'charcoal_gold') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INTEGER NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (generated_logo_id) REFERENCES generated_logos(id) ON DELETE CASCADE,
    INDEX idx_logo_color_scheme (generated_logo_id, color_scheme),
    UNIQUE KEY unique_logo_color_scheme (generated_logo_id, color_scheme)
);
```

### Migration Files

#### `create_logo_generations_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logo_generations', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->string('business_name');
            $table->text('business_description')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                  ->default('pending')
                  ->index();
            $table->integer('total_logos_requested')->default(12);
            $table->integer('logos_completed')->default(0);
            $table->string('api_provider', 50)->default('openai');
            $table->integer('cost_cents')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logo_generations');
    }
};
```

#### `create_generated_logos_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_logos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logo_generation_id')
                  ->constrained('logo_generations')
                  ->onDelete('cascade');
            $table->enum('style', ['minimalist', 'modern', 'playful', 'corporate'])
                  ->index();
            $table->integer('variation_number')->default(1);
            $table->text('prompt_used');
            $table->string('original_file_path', 500);
            $table->integer('file_size');
            $table->integer('image_width')->default(1024);
            $table->integer('image_height')->default(1024);
            $table->integer('generation_time_ms');
            $table->string('api_image_url', 1000)->nullable();
            $table->timestamps();

            $table->index(['logo_generation_id', 'style']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_logos');
    }
};
```

#### `create_logo_color_variants_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logo_color_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generated_logo_id')
                  ->constrained('generated_logos')
                  ->onDelete('cascade');
            $table->enum('color_scheme', [
                'monochrome', 'ocean_blue', 'forest_green', 'warm_sunset', 
                'royal_purple', 'corporate_navy', 'earthy_tones', 'tech_blue',
                'vibrant_pink', 'charcoal_gold'
            ])->index();
            $table->string('file_path', 500);
            $table->integer('file_size');
            $table->timestamps();

            $table->unique(['generated_logo_id', 'color_scheme']);
            $table->index(['generated_logo_id', 'color_scheme']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logo_color_variants');
    }
};
```

## Rationale

### Session-Based Tracking
- Uses session IDs instead of user authentication to maintain the current anonymous workflow
- Allows for easy cleanup of old data while preserving active sessions
- Enables sharing of logo generation results via session-based URLs

### Status Tracking
- Enum status field allows for proper job queue management and UI state updates
- Separate tracking of requested vs completed logos enables partial results display
- Error message storage helps with debugging and user feedback

### Cost Management
- Tracks API costs per generation request for usage analytics and billing if needed
- Enables cost-based rate limiting and budget controls
- Helps optimize API provider selection based on cost effectiveness

### Performance Optimization
- Indexed fields for common query patterns (session_id, status, style)
- Foreign key constraints ensure data integrity
- Separate table for individual logos allows for efficient querying by style or variation