# Database Schema

This is the database schema implementation for the spec detailed in @.agent-os/specs/2025-08-27-chatgpt-style-ui-redesign/spec.md

> Created: 2025-08-27
> Version: 1.0.0

## New Tables

### ideas

Stores the main idea records with their content and metadata.

```sql
CREATE TABLE ideas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug VARCHAR(255) NOT NULL UNIQUE,
    title VARCHAR(255),
    description TEXT NOT NULL,
    session_id VARCHAR(255),
    metadata JSON,
    is_starred BOOLEAN DEFAULT FALSE,
    last_accessed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

CREATE INDEX idx_ideas_slug ON ideas(slug);
CREATE INDEX idx_ideas_session_id ON ideas(session_id);
CREATE INDEX idx_ideas_created_at ON ideas(created_at DESC);
CREATE INDEX idx_ideas_last_accessed_at ON ideas(last_accessed_at DESC);
CREATE INDEX idx_ideas_deleted_at ON ideas(deleted_at);
CREATE INDEX idx_ideas_is_starred ON ideas(is_starred);
```

**Column Specifications:**
- `id`: Primary key, auto-incrementing
- `slug`: Unique identifier for URL routing, generated from title + timestamp
- `title`: Auto-generated or user-provided title for sidebar display
- `description`: The original idea text entered by user
- `session_id`: Links to browser session for anonymous users
- `metadata`: JSON field for flexible data (tags, categories, etc.)
- `is_starred`: Boolean flag for favoriting ideas
- `last_accessed_at`: Track when idea was last viewed for sorting
- `deleted_at`: Soft delete support

### idea_generations

Stores the history of all generations (names, logos, etc.) for each idea.

```sql
CREATE TABLE idea_generations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    idea_id INTEGER NOT NULL,
    generation_type VARCHAR(50) NOT NULL,
    input_parameters JSON,
    results JSON NOT NULL,
    model_used VARCHAR(100),
    processing_time_ms INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idea_id) REFERENCES ideas(id) ON DELETE CASCADE
);

CREATE INDEX idx_idea_generations_idea_id ON idea_generations(idea_id);
CREATE INDEX idx_idea_generations_type ON idea_generations(generation_type);
CREATE INDEX idx_idea_generations_created_at ON idea_generations(created_at DESC);
```

**Column Specifications:**
- `idea_id`: Foreign key to ideas table
- `generation_type`: Type of generation (names, logos, domains, etc.)
- `input_parameters`: JSON of parameters used for generation
- `results`: JSON array of generated results
- `model_used`: Which AI model was used (gpt-4, dall-e, etc.)
- `processing_time_ms`: Performance tracking

### idea_favorites

Stores user's favorited results from generations.

```sql
CREATE TABLE idea_favorites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    idea_id INTEGER NOT NULL,
    generation_id INTEGER NOT NULL,
    result_index INTEGER NOT NULL,
    result_data JSON NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idea_id) REFERENCES ideas(id) ON DELETE CASCADE,
    FOREIGN KEY (generation_id) REFERENCES idea_generations(id) ON DELETE CASCADE
);

CREATE INDEX idx_idea_favorites_idea_id ON idea_favorites(idea_id);
CREATE UNIQUE INDEX idx_idea_favorites_unique ON idea_favorites(generation_id, result_index);
```

## Modified Tables

### Existing Tables to Update

If there are existing tables for name generation or logo generation, they should be migrated to the new structure or linked via foreign keys to maintain backward compatibility.

## Migration Strategy

### Migration File: create_ideas_tables.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ideas', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->text('description');
            $table->string('session_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->boolean('is_starred')->default(false)->index();
            $table->timestamp('last_accessed_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('created_at');
            $table->index(['deleted_at', 'created_at']);
        });

        Schema::create('idea_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained()->cascadeOnDelete();
            $table->string('generation_type', 50)->index();
            $table->json('input_parameters')->nullable();
            $table->json('results');
            $table->string('model_used', 100)->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->timestamps();
            
            $table->index(['idea_id', 'created_at']);
        });

        Schema::create('idea_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained()->cascadeOnDelete();
            $table->foreignId('generation_id')
                ->references('id')
                ->on('idea_generations')
                ->cascadeOnDelete();
            $table->integer('result_index');
            $table->json('result_data');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['generation_id', 'result_index']);
            $table->index('idea_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idea_favorites');
        Schema::dropIfExists('idea_generations');
        Schema::dropIfExists('ideas');
    }
};
```

## Data Migration Considerations

### Migrating Existing Data

If there's existing session or generation data:

1. Create temporary migration to move existing data to new structure
2. Map old session data to new ideas table
3. Convert existing generations to idea_generations format
4. Maintain backward compatibility during transition period

### Performance Optimizations

1. **Indexing Strategy**
   - Index on slug for URL lookups
   - Composite index on (session_id, created_at) for user's idea list
   - Index on last_accessed_at for "recently viewed" queries

2. **JSON Column Optimization**
   - Use JSON columns for flexibility
   - Consider generated columns for frequently queried JSON fields
   - Implement database-level JSON validation where supported

3. **Query Optimization**
   - Use eager loading for generations when loading idea detail
   - Implement query scopes for common filters (starred, recent, etc.)
   - Cache frequently accessed ideas in Redis

## Seeding Strategy

### Development Seeder

```php
<?php

namespace Database\Seeders;

use App\Models\Idea;
use App\Models\IdeaGeneration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IdeaSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample ideas for development
        $ideas = [
            'AI-powered fitness app for busy professionals',
            'Sustainable packaging solution for e-commerce',
            'Language learning platform using VR technology',
            'Mental health tracking app for teenagers',
            'Blockchain-based supply chain management'
        ];

        foreach ($ideas as $index => $description) {
            $idea = Idea::create([
                'slug' => Str::slug(Str::words($description, 3) . '-' . now()->timestamp),
                'title' => Str::words($description, 4, '...'),
                'description' => $description,
                'session_id' => 'dev-session-' . ($index + 1),
                'metadata' => [
                    'source' => 'seeder',
                    'tags' => ['sample', 'development']
                ],
                'is_starred' => $index < 2,
                'last_accessed_at' => now()->subDays(rand(0, 30))
            ]);

            // Add sample generations
            IdeaGeneration::create([
                'idea_id' => $idea->id,
                'generation_type' => 'names',
                'input_parameters' => ['style' => 'creative', 'count' => 10],
                'results' => [
                    ['name' => 'Sample Name 1', 'domain_available' => true],
                    ['name' => 'Sample Name 2', 'domain_available' => false]
                ],
                'model_used' => 'gpt-4',
                'processing_time_ms' => rand(800, 2000)
            ]);
        }
    }
}
```

## Rollback Strategy

1. Backup existing data before migration
2. Implement down() methods properly in migrations
3. Test rollback in staging environment
4. Document any manual steps required for rollback
5. Maintain old table structure temporarily if needed for gradual migration