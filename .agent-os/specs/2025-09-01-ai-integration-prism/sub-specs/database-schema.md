# Database Schema

This is the database schema implementation for the spec detailed in @.agent-os/specs/2025-09-01-ai-integration-prism/spec.md

> Created: 2025-09-01
> Version: 1.0.0

## Schema Changes

### Modify Existing Tables

#### Update name_suggestions table
Add AI-specific columns to existing name_suggestions table:

```sql
ALTER TABLE name_suggestions ADD COLUMN ai_model VARCHAR(50) NULL;
ALTER TABLE name_suggestions ADD COLUMN generation_metadata JSON NULL;
ALTER TABLE name_suggestions ADD COLUMN generation_session_id VARCHAR(36) NULL;

-- Add indexes for efficient querying
CREATE INDEX idx_name_suggestions_ai_model ON name_suggestions(ai_model);
CREATE INDEX idx_name_suggestions_generation_session ON name_suggestions(generation_session_id);
```

#### Update projects table
Add AI generation preferences:

```sql
ALTER TABLE projects ADD COLUMN ai_preferences JSON NULL;
ALTER TABLE projects ADD COLUMN last_generation_at TIMESTAMP NULL;

-- Add index for generation tracking
CREATE INDEX idx_projects_last_generation ON projects(last_generation_at);
```

### New Tables

#### ai_generations table
Track AI generation sessions and performance:

```sql
CREATE TABLE ai_generations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NULL,
    business_idea TEXT NOT NULL,
    generation_mode VARCHAR(50) NOT NULL,
    deep_thinking BOOLEAN NOT NULL DEFAULT FALSE,
    models_requested JSON NOT NULL,
    models_completed JSON NOT NULL,
    total_names_generated INT NOT NULL DEFAULT 0,
    generation_time_ms INT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    error_details JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_ai_generations_user_id (user_id),
    INDEX idx_ai_generations_project_id (project_id),
    INDEX idx_ai_generations_status (status),
    INDEX idx_ai_generations_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
```

#### ai_model_performance table
Track model performance metrics:

```sql
CREATE TABLE ai_model_performance (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ai_generation_id BIGINT UNSIGNED NOT NULL,
    model_name VARCHAR(50) NOT NULL,
    response_time_ms INT NOT NULL,
    names_generated INT NOT NULL,
    tokens_used INT NULL,
    success_rate DECIMAL(5,2) NOT NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP NULL,
    
    INDEX idx_performance_generation_id (ai_generation_id),
    INDEX idx_performance_model_name (model_name),
    INDEX idx_performance_created_at (created_at),
    
    FOREIGN KEY (ai_generation_id) REFERENCES ai_generations(id) ON DELETE CASCADE
);
```

#### user_ai_preferences table
Store user model preferences and settings:

```sql
CREATE TABLE user_ai_preferences (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    preferred_models JSON NOT NULL,
    default_generation_mode VARCHAR(50) NOT NULL DEFAULT 'creative',
    enable_deep_thinking BOOLEAN NOT NULL DEFAULT FALSE,
    auto_generate_on_create BOOLEAN NOT NULL DEFAULT FALSE,
    model_weights JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    UNIQUE KEY unique_user_preferences (user_id),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Migration Files

### Migration 1: Update existing tables
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('name_suggestions', function (Blueprint $table) {
            $table->string('ai_model', 50)->nullable()->after('generation_metadata');
            $table->json('generation_metadata')->nullable()->after('logos');
            $table->uuid('generation_session_id')->nullable()->after('generation_metadata');
            
            $table->index(['ai_model'], 'idx_name_suggestions_ai_model');
            $table->index(['generation_session_id'], 'idx_name_suggestions_generation_session');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->json('ai_preferences')->nullable()->after('selected_name_id');
            $table->timestamp('last_generation_at')->nullable()->after('ai_preferences');
            
            $table->index(['last_generation_at'], 'idx_projects_last_generation');
        });
    }

    public function down(): void
    {
        Schema::table('name_suggestions', function (Blueprint $table) {
            $table->dropIndex('idx_name_suggestions_ai_model');
            $table->dropIndex('idx_name_suggestions_generation_session');
            $table->dropColumn(['ai_model', 'generation_metadata', 'generation_session_id']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('idx_projects_last_generation');
            $table->dropColumn(['ai_preferences', 'last_generation_at']);
        });
    }
};
```

### Migration 2: Create new AI tables
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
            $table->text('business_idea');
            $table->string('generation_mode', 50);
            $table->boolean('deep_thinking')->default(false);
            $table->json('models_requested');
            $table->json('models_completed')->default('[]');
            $table->integer('total_names_generated')->default(0);
            $table->integer('generation_time_ms')->nullable();
            $table->string('status', 50)->default('pending');
            $table->json('error_details')->nullable();
            $table->timestamps();
            
            $table->index(['user_id'], 'idx_ai_generations_user_id');
            $table->index(['project_id'], 'idx_ai_generations_project_id');
            $table->index(['status'], 'idx_ai_generations_status');
            $table->index(['created_at'], 'idx_ai_generations_created_at');
        });

        Schema::create('ai_model_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_generation_id')->constrained()->onDelete('cascade');
            $table->string('model_name', 50);
            $table->integer('response_time_ms');
            $table->integer('names_generated');
            $table->integer('tokens_used')->nullable();
            $table->decimal('success_rate', 5, 2);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->nullable();
            
            $table->index(['ai_generation_id'], 'idx_performance_generation_id');
            $table->index(['model_name'], 'idx_performance_model_name');
            $table->index(['created_at'], 'idx_performance_created_at');
        });

        Schema::create('user_ai_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('preferred_models');
            $table->string('default_generation_mode', 50)->default('creative');
            $table->boolean('enable_deep_thinking')->default(false);
            $table->boolean('auto_generate_on_create')->default(false);
            $table->json('model_weights')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id'], 'unique_user_preferences');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ai_preferences');
        Schema::dropIfExists('ai_model_performance');
        Schema::dropIfExists('ai_generations');
    }
};
```

## Data Integrity and Constraints

### Validation Rules
- `ai_model` must be one of: 'gpt-4o', 'claude-3.5-sonnet', 'gemini-1.5-pro', 'grok-beta'
- `generation_mode` must be one of: 'creative', 'professional', 'brandable', 'tech-focused'
- `status` must be one of: 'pending', 'processing', 'completed', 'failed', 'cancelled'
- `models_requested` array must contain at least one valid model name
- `success_rate` must be between 0.00 and 100.00

### Performance Considerations
- Indexes on frequently queried columns (user_id, project_id, ai_model, status)
- JSON columns for flexible metadata storage without schema changes
- Appropriate foreign key constraints with CASCADE delete for data cleanup
- Consider partitioning ai_generations and ai_model_performance tables by date for large-scale usage

### Data Cleanup Strategy
- Automatic cleanup of failed generations older than 30 days
- Archive completed generations older than 1 year to separate table
- Maintain performance metrics for analysis and model optimization
- Regular cleanup of orphaned generation sessions