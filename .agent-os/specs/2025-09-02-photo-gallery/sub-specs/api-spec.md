# API Specification

This is the API specification for the spec detailed in @.agent-os/specs/2025-09-02-photo-gallery/spec.md

> Created: 2025-09-02
> Version: 1.0.0

## Image Upload API Routes

### POST /api/images/upload
**Purpose:** Upload one or more image files to a project
**Authentication:** Required
**Parameters:**
- `project_id` (integer, required) - Target project ID
- `files` (array of files, required) - Image files to upload
- `tags` (array of strings, optional) - Tags to apply to uploaded images
- `mood_board_id` (integer, optional) - Auto-add to specified mood board

**Response:**
```json
{
  "success": true,
  "message": "Images uploaded successfully",
  "data": {
    "uploaded_count": 3,
    "images": [
      {
        "id": 123,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "original_filename": "inspiration.jpg",
        "file_path": "projects/1/images/originals/550e8400.jpg",
        "thumbnail_path": "projects/1/images/thumbnails/medium/550e8400.jpg",
        "processing_status": "processing",
        "created_at": "2025-09-02T10:30:00Z"
      }
    ]
  }
}
```

**Errors:**
- 422: Invalid file type or size
- 413: File too large
- 403: Project access denied

### POST /api/images/upload/chunked
**Purpose:** Handle chunked upload for large files
**Parameters:**
- `chunk` (file, required) - Current chunk
- `chunk_index` (integer, required) - Chunk position
- `total_chunks` (integer, required) - Total number of chunks
- `upload_session_id` (string, required) - Unique session identifier
- `project_id` (integer, required) - Target project ID

## Image Management API Routes

### GET /api/projects/{project}/images
**Purpose:** List images in a project with filtering and pagination
**Parameters:**
- `page` (integer, optional) - Page number (default: 1)
- `per_page` (integer, optional) - Items per page (default: 24, max: 100)
- `search` (string, optional) - Search in filenames and tags
- `tags` (array, optional) - Filter by specific tags
- `processing_status` (string, optional) - Filter by processing status
- `sort` (string, optional) - Sort order: created_at, filename, file_size
- `order` (string, optional) - asc or desc (default: desc)

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "original_filename": "inspiration.jpg",
      "title": "Brand Inspiration",
      "description": "Modern minimalist design reference",
      "file_size": 1024000,
      "width": 1920,
      "height": 1080,
      "thumbnail_url": "https://cdn.example.com/thumbnails/550e8400.jpg",
      "tags": ["minimalist", "modern", "blue"],
      "dominant_colors": ["#2E86AB", "#A23B72", "#F18F01"],
      "processing_status": "completed",
      "created_at": "2025-09-02T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 24,
    "total": 156,
    "last_page": 7
  }
}
```

### PUT /api/images/{image}
**Purpose:** Update image metadata
**Parameters:**
- `title` (string, optional) - Image title
- `description` (string, optional) - Image description  
- `tags` (array, optional) - Replace existing tags

### DELETE /api/images/{image}
**Purpose:** Delete an image and all associated files
**Response:**
```json
{
  "success": true,
  "message": "Image deleted successfully"
}
```

### GET /api/images/{image}/download
**Purpose:** Download original image file
**Parameters:**
- `size` (string, optional) - Thumbnail size: small, medium, large, original

## Mood Board API Routes

### POST /api/mood-boards
**Purpose:** Create a new mood board
**Parameters:**
- `project_id` (integer, required) - Target project
- `name` (string, required) - Board name
- `description` (string, optional) - Board description
- `layout_type` (string, optional) - grid, collage, masonry, freeform
- `is_public` (boolean, optional) - Public sharing enabled

**Response:**
```json
{
  "data": {
    "id": 456,
    "uuid": "660f9500-f39c-42e5-b827-556755551111",
    "name": "Modern Tech Vibes",
    "description": "Clean, modern aesthetic for tech startup",
    "layout_type": "grid",
    "is_public": false,
    "share_token": null,
    "image_count": 0,
    "created_at": "2025-09-02T11:00:00Z"
  }
}
```

### PUT /api/mood-boards/{board}
**Purpose:** Update mood board settings
**Parameters:**
- `name` (string, optional) - Board name
- `description` (string, optional) - Board description
- `layout_type` (string, optional) - Layout type
- `is_public` (boolean, optional) - Toggle public sharing

### POST /api/mood-boards/{board}/images
**Purpose:** Add images to mood board with positioning
**Parameters:**
- `image_ids` (array of integers, required) - Image IDs to add
- `positions` (array of objects, optional) - Position data for each image
  ```json
  [
    {
      "image_id": 123,
      "position": 0,
      "x_position": 100,
      "y_position": 50,
      "width": 300,
      "height": 200
    }
  ]
  ```

### PUT /api/mood-boards/{board}/images/{image}/position
**Purpose:** Update image position within mood board
**Parameters:**
- `position` (integer, optional) - Order position
- `x_position` (integer, optional) - X coordinate
- `y_position` (integer, optional) - Y coordinate
- `width` (integer, optional) - Display width
- `height` (integer, optional) - Display height

### DELETE /api/mood-boards/{board}/images/{image}
**Purpose:** Remove image from mood board

### GET /api/mood-boards/{board}/export
**Purpose:** Export mood board as PDF or high-resolution image
**Parameters:**
- `format` (string, optional) - pdf, png, jpg (default: pdf)
- `resolution` (string, optional) - low, medium, high (default: medium)

## Public Sharing Routes

### GET /public/mood-boards/{token}
**Purpose:** View publicly shared mood board
**Response:** HTML page with embedded mood board viewer

### GET /api/public/mood-boards/{token}
**Purpose:** Get public mood board data via API
**Response:**
```json
{
  "data": {
    "name": "Modern Tech Vibes",
    "description": "Clean, modern aesthetic for tech startup",
    "layout_type": "grid",
    "images": [
      {
        "thumbnail_url": "https://cdn.example.com/thumbnails/550e8400.jpg",
        "position": 0,
        "width": 300,
        "height": 200
      }
    ],
    "created_at": "2025-09-02T11:00:00Z"
  }
}
```

## Theme Customization Routes

### GET /api/user/theme
**Purpose:** Get current user's theme configuration
**Response:**
```json
{
  "data": {
    "theme_name": "custom",
    "is_custom_theme": true,
    "colors": {
      "primary": "#3B82F6",
      "secondary": "#8B5CF6", 
      "accent": "#10B981",
      "background": "#FFFFFF",
      "surface": "#F8FAFC",
      "text_primary": "#1F2937",
      "text_secondary": "#6B7280"
    },
    "dark_colors": {
      "background": "#111827",
      "surface": "#1F2937",
      "text_primary": "#F9FAFB",
      "text_secondary": "#D1D5DB"
    },
    "preferences": {
      "border_radius": "medium",
      "font_size": "medium",
      "compact_mode": false
    }
  }
}
```

### PUT /api/user/theme
**Purpose:** Update user's theme configuration
**Parameters:**
- `theme_name` (string, optional) - Predefined theme name or "custom"
- `colors` (object, optional) - Color scheme configuration
- `dark_colors` (object, optional) - Dark mode color variants
- `preferences` (object, optional) - UI preference settings

### GET /api/themes/presets
**Purpose:** Get available predefined themes
**Response:**
```json
{
  "data": [
    {
      "name": "default",
      "display_name": "Default Blue",
      "preview_colors": ["#3B82F6", "#8B5CF6", "#10B981"],
      "description": "Clean and professional blue theme"
    },
    {
      "name": "creative",
      "display_name": "Creative Purple", 
      "preview_colors": ["#8B5CF6", "#EC4899", "#F59E0B"],
      "description": "Bold and creative purple-pink theme"
    }
  ]
}
```

### GET /api/themes/{theme}/css
**Purpose:** Get CSS custom properties for a specific theme
**Response:** CSS content with theme variables

## AI Integration Routes

### POST /api/images/analyze
**Purpose:** Trigger AI analysis for specific images
**Parameters:**
- `image_ids` (array of integers, required) - Images to analyze
- `analysis_type` (string, optional) - style, mood, color, objects

### GET /api/images/{image}/analysis
**Purpose:** Get AI analysis results for an image
**Response:**
```json
{
  "data": {
    "style_analysis": {
      "primary_style": "minimalist",
      "confidence": 0.87,
      "elements": ["clean lines", "white space", "geometric shapes"]
    },
    "color_analysis": {
      "dominant_colors": ["#2E86AB", "#A23B72", "#F18F01"],
      "color_mood": "professional",
      "contrast_level": "high"
    },
    "mood_analysis": {
      "primary_mood": "modern",
      "secondary_moods": ["professional", "clean", "trustworthy"],
      "emotional_score": 0.72
    }
  }
}
```

## Rate Limiting

- Image uploads: 50 requests per hour per user
- Image analysis: 100 requests per hour per user
- General API: 1000 requests per hour per user
- Public mood board access: 500 requests per hour per IP

## Error Responses

All error responses follow this format:
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Specific validation error"]
  }
}
```