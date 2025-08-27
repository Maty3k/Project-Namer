# Database Schema

This is the database schema implementation for the spec detailed in @.agent-os/specs/2025-08-27-chatgpt-style-sidebar/spec.md

> Created: 2025-08-27
> Version: 1.0.0

## Schema Changes

### New Tables

#### `naming_sessions` Table
```sql
CREATE TABLE naming_sessions (
    id CHAR(36) PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    business_description TEXT,
    generation_mode VARCHAR(50) DEFAULT 'creative',
    deep_thinking BOOLEAN DEFAULT FALSE,
    is_starred BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    last_accessed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_sessions (user_id, created_at DESC),
    INDEX idx_user_starred (user_id, is_starred, created_at DESC),
    INDEX idx_last_accessed (user_id, last_accessed_at DESC),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### `session_results` Table
```sql
CREATE TABLE session_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id CHAR(36) NOT NULL,
    generated_names JSON NOT NULL,
    domain_results JSON NOT NULL,
    selected_for_logos JSON DEFAULT NULL,
    generation_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_session_results (session_id, generation_timestamp DESC),
    FOREIGN KEY (session_id) REFERENCES naming_sessions(id) ON DELETE CASCADE
);
```

#### `session_search` Table (FTS5 Virtual Table for SQLite)
```sql
CREATE VIRTUAL TABLE session_search USING fts5(
    session_id,
    title,
    business_description,
    generated_names,
    content=naming_sessions,
    content_rowid=id
);

-- Trigger to keep search index updated
CREATE TRIGGER sessions_ai AFTER INSERT ON naming_sessions BEGIN
  INSERT INTO session_search(session_id, title, business_description) 
  VALUES (new.id, new.title, new.business_description);
END;

CREATE TRIGGER sessions_au AFTER UPDATE ON naming_sessions BEGIN
  UPDATE session_search 
  SET title = new.title, business_description = new.business_description
  WHERE session_id = new.id;
END;

CREATE TRIGGER sessions_ad AFTER DELETE ON naming_sessions BEGIN
  DELETE FROM session_search WHERE session_id = old.id;
END;
```

### Modifications to Existing Tables

None required - the session system is additive and doesn't modify existing tables.

## Migration Files

### Create Naming Sessions Migration
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('naming_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('business_description')->nullable();
            $table->string('generation_mode', 50)->default('creative');
            $table->boolean('deep_thinking')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'is_starred', 'created_at']);
            $table->index(['user_id', 'last_accessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('naming_sessions');
    }
};
```

### Create Session Results Migration
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id');
            $table->json('generated_names');
            $table->json('domain_results');
            $table->json('selected_for_logos')->nullable();
            $table->timestamp('generation_timestamp')->useCurrent();
            $table->timestamps();
            
            $table->foreign('session_id')
                ->references('id')
                ->on('naming_sessions')
                ->cascadeOnDelete();
                
            $table->index(['session_id', 'generation_timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_results');
    }
};
```

## Data Migration Strategy

1. **Existing User Data**: Create a default session for each user containing their current work
2. **Search History**: Import existing search history as individual sessions
3. **Backward Compatibility**: Maintain current dashboard functionality during transition

## Performance Optimizations

- Composite indexes for common query patterns
- JSON columns for flexible schema evolution
- UUID primary keys for distributed systems compatibility
- Appropriate foreign key constraints with cascade deletes
- FTS5 virtual table for performant full-text search