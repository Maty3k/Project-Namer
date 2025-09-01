# Database Schema

This is the database schema implementation for the spec detailed in @.agent-os/specs/2025-09-01-project-workflow-ui/spec.md

> Created: 2025-09-01
> Version: 1.0.0

## Schema Changes

### New Tables

#### projects
```sql
CREATE TABLE projects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    selected_name_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_projects_uuid (uuid),
    INDEX idx_projects_user_id (user_id),
    INDEX idx_projects_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (selected_name_id) REFERENCES name_suggestions(id) ON DELETE SET NULL
);
```

#### name_suggestions
```sql
CREATE TABLE name_suggestions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    domains JSON NULL, -- Stores array of domain objects with extension and availability
    logos JSON NULL, -- Stores array of logo URLs or generation data
    is_hidden BOOLEAN DEFAULT FALSE,
    generation_metadata JSON NULL, -- Stores AI model used, parameters, etc.
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_name_suggestions_project_id (project_id),
    INDEX idx_name_suggestions_is_hidden (is_hidden),
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
```

## Migration Files

### Create Projects Table Migration
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description');
            $table->foreignId('selected_name_id')->nullable()->constrained('name_suggestions')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            $table->index('uuid');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
```

### Create Name Suggestions Table Migration
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('name_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('domains')->nullable();
            $table->json('logos')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->json('generation_metadata')->nullable();
            $table->timestamps();
            
            $table->index('project_id');
            $table->index('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('name_suggestions');
    }
};
```

### Update Projects Table for Selected Name (Second Migration)
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This migration runs after both tables are created
        // to add the foreign key reference
        if (!Schema::hasColumn('projects', 'selected_name_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->foreignId('selected_name_id')
                    ->nullable()
                    ->after('description')
                    ->constrained('name_suggestions')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['selected_name_id']);
            $table->dropColumn('selected_name_id');
        });
    }
};
```

## Model Relationships

### Project Model
```php
class Project extends Model
{
    protected $fillable = ['uuid', 'name', 'description', 'user_id', 'selected_name_id'];
    
    protected $casts = [
        'uuid' => 'string',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function nameSuggestions(): HasMany
    {
        return $this->hasMany(NameSuggestion::class);
    }
    
    public function selectedName(): BelongsTo
    {
        return $this->belongsTo(NameSuggestion::class, 'selected_name_id');
    }
    
    public function visibleNameSuggestions(): HasMany
    {
        return $this->hasMany(NameSuggestion::class)->where('is_hidden', false);
    }
}
```

### NameSuggestion Model
```php
class NameSuggestion extends Model
{
    protected $fillable = ['project_id', 'name', 'domains', 'logos', 'is_hidden', 'generation_metadata'];
    
    protected $casts = [
        'domains' => 'array',
        'logos' => 'array',
        'is_hidden' => 'boolean',
        'generation_metadata' => 'array',
    ];
    
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }
    
    public function scopeHidden($query)
    {
        return $query->where('is_hidden', true);
    }
}
```

## Data Structure Examples

### Domains JSON Structure
```json
[
    {
        "extension": ".com",
        "available": true,
        "price": 12.99,
        "registrar": "namecheap"
    },
    {
        "extension": ".io",
        "available": false,
        "alternatives": [".dev", ".app"]
    }
]
```

### Logos JSON Structure
```json
[
    {
        "url": "https://storage/logos/uuid-1.png",
        "style": "minimalist",
        "colors": ["#FF5733", "#33FF57"],
        "generated_at": "2025-09-01T10:00:00Z"
    },
    {
        "url": "https://storage/logos/uuid-2.svg",
        "style": "modern",
        "colors": ["#000000", "#FFFFFF"],
        "generated_at": "2025-09-01T10:01:00Z"
    }
]
```

### Generation Metadata JSON Structure
```json
{
    "ai_model": "gpt-4",
    "prompt_template": "creative_tech",
    "temperature": 0.7,
    "generated_at": "2025-09-01T10:00:00Z",
    "generation_time_ms": 1250
}
```

## Indexing Strategy

1. **UUID Index on projects table** - For fast lookups when accessing project pages
2. **User ID Index on projects table** - For efficient queries when listing user's projects
3. **Created At Index on projects table** - For chronological ordering in sidebar
4. **Project ID Index on name_suggestions** - For fast retrieval of suggestions per project
5. **Is Hidden Index on name_suggestions** - For efficient filtering of visible/hidden names

## Migration Order

1. Create users table (existing)
2. Create projects table (without selected_name_id)
3. Create name_suggestions table
4. Add selected_name_id foreign key to projects table

This order ensures all tables exist before creating foreign key relationships.