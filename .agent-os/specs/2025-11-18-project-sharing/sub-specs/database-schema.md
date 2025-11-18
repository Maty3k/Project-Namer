# Database Schema

This is the database schema implementation for the spec detailed in @.agent-os/specs/2025-11-18-project-sharing/spec.md

> Created: 2025-11-18
> Version: 1.0.0

## Schema Changes

### New Tables

#### `shares` Table
Stores shareable links with privacy settings and metadata.

```php
Schema::create('shares', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('session_id')->nullable()->constrained()->onDelete('cascade');
    $table->string('token', 32)->unique()->index();
    $table->string('title')->nullable();
    $table->text('description')->nullable();
    $table->json('names'); // Array of generated names
    $table->json('settings')->nullable(); // Privacy settings, etc.
    $table->string('password')->nullable(); // Hashed password
    $table->timestamp('expires_at')->nullable()->index();
    $table->unsignedInteger('view_count')->default(0);
    $table->boolean('is_active')->default(true)->index();
    $table->timestamps();
    $table->softDeletes();
});
```

**Indexes:**
- `token` - Unique index for fast lookups
- `user_id` - Foreign key index for user relationships
- `session_id` - Foreign key index for session relationships
- `expires_at` - Index for efficient expiration queries
- `is_active` - Index for active share filtering

**Rationale:**
- `token` provides secure, unique URL identifier
- `json` columns store flexible data without additional tables
- `password` enables optional protection
- `expires_at` allows automatic expiration
- `view_count` tracks engagement
- Soft deletes preserve data for analytics

#### `share_accesses` Table
Tracks analytics for each share view.

```php
Schema::create('share_accesses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('share_id')->constrained()->onDelete('cascade');
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->string('referer')->nullable();
    $table->string('country')->nullable();
    $table->timestamp('accessed_at')->index();
    $table->timestamps();
});
```

**Indexes:**
- `share_id` - Foreign key index for relationship
- `accessed_at` - Index for time-based analytics

**Rationale:**
- Provides detailed analytics for share performance
- `ip_address` varchar(45) supports both IPv4 and IPv6
- `user_agent` helps understand device/browser usage
- `referer` shows traffic sources
- Separate table keeps shares table clean

#### `exports` Table
Manages generated export files.

```php
Schema::create('exports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->uuid('uuid')->unique()->index();
    $table->morphs('exportable'); // Polymorphic relation (Share, Session)
    $table->enum('type', ['pdf', 'csv', 'json']);
    $table->string('filename');
    $table->string('file_path');
    $table->unsignedBigInteger('file_size')->nullable();
    $table->timestamp('expires_at')->nullable()->index();
    $table->unsignedInteger('download_count')->default(0);
    $table->timestamps();
});
```

**Indexes:**
- `uuid` - Unique identifier for download URLs
- `user_id` - Foreign key index
- `exportable` - Polymorphic indexes (exportable_type, exportable_id)
- `expires_at` - Index for cleanup queries

**Rationale:**
- `uuid` provides secure download URLs
- Polymorphic relationship allows exporting different entities
- `enum` type ensures valid export formats
- File metadata enables management and cleanup
- Expiration enables automatic cleanup of old exports

### Modified Tables

#### `sessions` Table
Add sharing-related fields to existing sessions table.

```php
Schema::table('sessions', function (Blueprint $table) {
    $table->boolean('is_shareable')->default(true);
    $table->unsignedInteger('share_count')->default(0);
});
```

**Rationale:**
- `is_shareable` allows users to mark sessions as private
- `share_count` tracks how many times session was shared

## Migration Strategy

1. Create `shares` table migration
2. Create `share_accesses` table migration
3. Create `exports` table migration
4. Create migration to add columns to `sessions` table
5. Run migrations in order

## Data Integrity

### Foreign Key Constraints
- `shares.user_id` → `users.id` (CASCADE on delete)
- `shares.session_id` → `sessions.id` (CASCADE on delete)
- `share_accesses.share_id` → `shares.id` (CASCADE on delete)
- `exports.user_id` → `users.id` (CASCADE on delete)

### Cascade Behavior
- Deleting a user cascades to all their shares, accesses, and exports
- Deleting a session cascades to associated shares
- Deleting a share cascades to all access records
- Soft deletes on shares preserve data while hiding from public

## Performance Considerations

### Indexing Strategy
- Token column indexed for fast share lookups
- Timestamps indexed for expiration cleanup jobs
- Foreign keys automatically indexed by Laravel
- Composite indexes not needed based on expected query patterns

### Data Cleanup
- Implement scheduled job to delete expired shares
- Implement scheduled job to delete old export files
- Keep share_accesses for 90 days for analytics
- Soft delete shares to preserve analytics data
