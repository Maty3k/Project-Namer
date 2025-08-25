# Database Schema

This is the database schema implementation for the spec detailed in @.agent-os/specs/2025-08-20-sharing-saving/spec.md

> Created: 2025-08-20
> Version: 1.0.0

## New Tables

### shares
```sql
CREATE TABLE shares (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    shareable_type VARCHAR(255) NOT NULL,
    shareable_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NULL,
    description TEXT NULL,
    share_type ENUM('public', 'password_protected') NOT NULL DEFAULT 'public',
    password_hash VARCHAR(255) NULL,
    expires_at TIMESTAMP NULL,
    view_count INT UNSIGNED DEFAULT 0,
    last_viewed_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    settings JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_uuid (uuid),
    INDEX idx_shareable (shareable_type, shareable_id),
    INDEX idx_user_id (user_id),
    INDEX idx_share_type (share_type),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_active (is_active)
);
```

### share_accesses
```sql
CREATE TABLE share_accesses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    share_id BIGINT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    referrer VARCHAR(500) NULL,
    accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (share_id) REFERENCES shares(id) ON DELETE CASCADE,
    INDEX idx_share_id (share_id),
    INDEX idx_accessed_at (accessed_at),
    INDEX idx_ip_address (ip_address)
);
```

### exports
```sql
CREATE TABLE exports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    exportable_type VARCHAR(255) NOT NULL,
    exportable_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    export_type ENUM('pdf', 'csv', 'json') NOT NULL,
    file_path VARCHAR(500) NULL,
    file_size BIGINT UNSIGNED NULL,
    download_count INT UNSIGNED DEFAULT 0,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_uuid (uuid),
    INDEX idx_exportable (exportable_type, exportable_id),
    INDEX idx_user_id (user_id),
    INDEX idx_export_type (export_type),
    INDEX idx_expires_at (expires_at)
);
```

## Modified Tables

### name_generations
```sql
-- Add sharing columns to existing table
ALTER TABLE name_generations 
ADD COLUMN share_count INT UNSIGNED DEFAULT 0,
ADD COLUMN last_shared_at TIMESTAMP NULL,
ADD INDEX idx_last_shared_at (last_shared_at);
```

## Migration Files Required

1. **2025_08_20_140001_create_shares_table.php**
   - Create shares table with all columns and indexes
   - Add foreign key constraints

2. **2025_08_20_140002_create_share_accesses_table.php**
   - Create share_accesses table for analytics
   - Add foreign key to shares table

3. **2025_08_20_140003_create_exports_table.php**
   - Create exports table for file generation tracking
   - Add polymorphic relationship support

4. **2025_08_20_140004_add_sharing_columns_to_name_generations.php**
   - Add share tracking columns to existing name_generations table
   - Add appropriate indexes for performance

## Indexes and Performance

- Primary indexes on all ID columns for fast lookups
- UUID indexes for share URL resolution
- Composite indexes on polymorphic relationships
- Date-based indexes for cleanup and analytics queries
- IP address indexing for rate limiting and analytics

## Data Integrity

- Foreign key constraints ensure referential integrity
- Enum constraints limit share and export types to valid values
- JSON validation for settings column
- NOT NULL constraints on essential fields
- Default values for tracking columns

## Security Considerations

- Password hashes stored using bcrypt algorithm
- UUIDs provide secure, non-guessable share identifiers
- IP address tracking for security monitoring
- Expiration date support for time-limited shares
- Soft delete capability through is_active flag