# Database Schema

This is the database schema implementation for the spec detailed in @.agent-os/specs/2025-09-02-photo-gallery/spec.md

> Created: 2025-09-02
> Version: 1.0.0

## New Tables

### project_images
Stores uploaded images associated with projects.

```sql
CREATE TABLE project_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    project_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    
    -- File information
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    thumbnail_path VARCHAR(500),
    file_size INTEGER NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    
    -- Image metadata
    width INTEGER,
    height INTEGER,
    aspect_ratio DECIMAL(5,2),
    dominant_colors JSON,
    
    -- Organization
    title VARCHAR(255),
    description TEXT,
    tags JSON,
    
    -- Status
    processing_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    is_public BOOLEAN DEFAULT FALSE,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_project_images_project (project_id),
    INDEX idx_project_images_user (user_id),
    INDEX idx_project_images_status (processing_status),
    INDEX idx_project_images_created (created_at),
    
    -- Foreign keys
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### mood_boards
Stores user-created mood boards for visual inspiration.

```sql
CREATE TABLE mood_boards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    project_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    
    -- Board information
    name VARCHAR(255) NOT NULL,
    description TEXT,
    layout_type ENUM('grid', 'collage', 'masonry', 'freeform') DEFAULT 'grid',
    layout_config JSON,
    
    -- Sharing
    is_public BOOLEAN DEFAULT FALSE,
    share_token VARCHAR(255) UNIQUE,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_mood_boards_project (project_id),
    INDEX idx_mood_boards_user (user_id),
    INDEX idx_mood_boards_share (share_token),
    
    -- Foreign keys
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### mood_board_images
Junction table for images in mood boards.

```sql
CREATE TABLE mood_board_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mood_board_id BIGINT UNSIGNED NOT NULL,
    project_image_id BIGINT UNSIGNED NOT NULL,
    
    -- Positioning
    position INTEGER NOT NULL DEFAULT 0,
    x_position INTEGER,
    y_position INTEGER,
    width INTEGER,
    height INTEGER,
    z_index INTEGER DEFAULT 0,
    
    -- Metadata
    notes TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_mood_board_images_board (mood_board_id),
    INDEX idx_mood_board_images_position (position),
    UNIQUE KEY unique_board_image (mood_board_id, project_image_id),
    
    -- Foreign keys
    FOREIGN KEY (mood_board_id) REFERENCES mood_boards(id) ON DELETE CASCADE,
    FOREIGN KEY (project_image_id) REFERENCES project_images(id) ON DELETE CASCADE
);
```

### image_generation_context
Links images used as context for name/logo generation.

```sql
CREATE TABLE image_generation_context (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_image_id BIGINT UNSIGNED NOT NULL,
    generation_session_id BIGINT UNSIGNED NOT NULL,
    generation_type ENUM('name', 'logo') NOT NULL,
    
    -- AI Analysis
    vision_analysis JSON,
    influence_score DECIMAL(3,2),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_image_context_session (generation_session_id),
    INDEX idx_image_context_image (project_image_id),
    
    -- Foreign keys
    FOREIGN KEY (project_image_id) REFERENCES project_images(id) ON DELETE CASCADE,
    FOREIGN KEY (generation_session_id) REFERENCES generation_sessions(id) ON DELETE CASCADE
);
```

### user_theme_preferences
Stores user UI customization preferences.

```sql
CREATE TABLE user_theme_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    
    -- Theme configuration
    theme_name VARCHAR(100) NOT NULL DEFAULT 'default',
    is_custom_theme BOOLEAN DEFAULT FALSE,
    
    -- Color scheme
    primary_color VARCHAR(7) NOT NULL DEFAULT '#3B82F6', -- Blue-500
    secondary_color VARCHAR(7) NOT NULL DEFAULT '#8B5CF6', -- Purple-500
    accent_color VARCHAR(7) NOT NULL DEFAULT '#10B981', -- Green-500
    background_color VARCHAR(7) NOT NULL DEFAULT '#FFFFFF',
    surface_color VARCHAR(7) NOT NULL DEFAULT '#F8FAFC',
    text_primary_color VARCHAR(7) NOT NULL DEFAULT '#1F2937',
    text_secondary_color VARCHAR(7) NOT NULL DEFAULT '#6B7280',
    
    -- Dark mode variants
    dark_background_color VARCHAR(7) NOT NULL DEFAULT '#111827',
    dark_surface_color VARCHAR(7) NOT NULL DEFAULT '#1F2937',
    dark_text_primary_color VARCHAR(7) NOT NULL DEFAULT '#F9FAFB',
    dark_text_secondary_color VARCHAR(7) NOT NULL DEFAULT '#D1D5DB',
    
    -- UI preferences
    border_radius ENUM('none', 'small', 'medium', 'large', 'full') DEFAULT 'medium',
    font_size ENUM('small', 'medium', 'large') DEFAULT 'medium',
    compact_mode BOOLEAN DEFAULT FALSE,
    
    -- Theme metadata
    theme_config JSON, -- Store complete CSS custom properties
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    UNIQUE KEY unique_user_theme (user_id),
    INDEX idx_theme_name (theme_name),
    
    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Modifications to Existing Tables

### projects table
Add image-related counters and settings.

```sql
ALTER TABLE projects 
ADD COLUMN total_images INTEGER DEFAULT 0,
ADD COLUMN storage_used_bytes BIGINT DEFAULT 0,
ADD COLUMN default_mood_board_id BIGINT UNSIGNED,
ADD COLUMN image_upload_enabled BOOLEAN DEFAULT TRUE;

ALTER TABLE projects
ADD FOREIGN KEY (default_mood_board_id) REFERENCES mood_boards(id) ON DELETE SET NULL;
```

### generation_sessions table
Add support for image context.

```sql
ALTER TABLE generation_sessions
ADD COLUMN used_image_context BOOLEAN DEFAULT FALSE,
ADD COLUMN context_image_count INTEGER DEFAULT 0;
```

### users table
Add theme preference tracking.

```sql
ALTER TABLE users
ADD COLUMN current_theme VARCHAR(100) DEFAULT 'default',
ADD COLUMN prefers_dark_mode BOOLEAN DEFAULT FALSE,
ADD COLUMN theme_auto_switch BOOLEAN DEFAULT TRUE; -- Auto dark/light based on system
```

## Rationale

- **Separate project_images table**: Allows flexible image management independent of specific features
- **mood_boards structure**: Supports multiple layout types and sharing capabilities
- **Junction table pattern**: Enables many-to-many relationships between boards and images
- **JSON columns**: For flexible metadata storage without schema changes
- **UUID fields**: For secure public sharing without exposing sequential IDs
- **Processing status**: Tracks image processing pipeline state
- **Storage tracking**: Helps monitor and limit storage usage per project