# API Specification

This is the API specification for the spec detailed in @.agent-os/specs/2025-08-19-logo-generation/spec.md

> Created: 2025-08-19
> Version: 1.0.0

## Endpoints

### POST /api/logos/generate

**Purpose:** Initiates logo generation for a selected business name
**Parameters:** 
- `business_name` (string, required): The business name to generate logos for
- `business_description` (string, optional): Additional context for logo generation
- `session_id` (string, required): Current user session identifier
- `styles` (array, optional): Specific styles to generate ['minimalist', 'modern', 'playful', 'corporate'], defaults to all

**Response:**
```json
{
  "success": true,
  "data": {
    "generation_id": 123,
    "session_id": "sess_abc123",
    "business_name": "TechFlow",
    "status": "pending",
    "total_logos_requested": 12,
    "estimated_completion_time": "2-3 minutes"
  }
}
```

**Errors:**
- 422: Validation errors (missing business_name, invalid session_id)
- 429: Rate limit exceeded
- 503: AI API service unavailable

### GET /api/logos/status/{generation_id}

**Purpose:** Retrieves the current status of a logo generation request
**Parameters:** 
- `generation_id` (integer, path): The ID of the generation request

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "status": "processing",
    "logos_completed": 6,
    "total_logos_requested": 12,
    "progress_percentage": 50,
    "estimated_time_remaining": "1-2 minutes",
    "error_message": null
  }
}
```

**Errors:**
- 404: Generation not found
- 403: Access denied (wrong session)

### GET /api/logos/{generation_id}

**Purpose:** Retrieves completed logos for a generation request with color scheme information
**Parameters:** 
- `generation_id` (integer, path): The ID of the generation request
- `style` (string, query, optional): Filter by specific style

**Response:**
```json
{
  "success": true,
  "data": {
    "generation": {
      "id": 123,
      "business_name": "TechFlow",
      "status": "completed",
      "total_logos": 12,
      "created_at": "2025-08-19T10:30:00Z"
    },
    "logos": [
      {
        "id": 1,
        "style": "minimalist",
        "variation_number": 1,
        "original_file_path": "/storage/logos/sess_abc123/originals/techflow-minimalist-1-uuid.svg",
        "download_url": "/api/logos/download/1",
        "thumbnail_url": "/api/logos/thumbnail/1",
        "file_size": 45678,
        "formats": ["png", "svg"],
        "color_variants": [
          {
            "color_scheme": "ocean_blue",
            "preview_url": "/api/logos/preview/1/ocean_blue",
            "download_url": "/api/logos/download/1?color_scheme=ocean_blue"
          }
        ]
      }
    ],
    "available_color_schemes": [
      {"id": "monochrome", "name": "Monochrome", "colors": ["#000000", "#666666", "#FFFFFF"]},
      {"id": "ocean_blue", "name": "Ocean Blue", "colors": ["#003366", "#0066CC", "#99CCFF"]}
    ]
  }
}
```

**Errors:**
- 404: Generation not found
- 403: Access denied (wrong session)

### POST /api/logos/{logo_id}/customize-color

**Purpose:** Applies a color scheme to a specific logo and returns the customized version
**Parameters:**
- `logo_id` (integer, path): The ID of the logo to customize
- `color_scheme` (string, required): The color scheme to apply ('monochrome', 'ocean_blue', etc.)

**Response:**
```json
{
  "success": true,
  "data": {
    "logo_id": 1,
    "color_scheme": "ocean_blue",
    "customized_logo_url": "/api/logos/download/1?color_scheme=ocean_blue",
    "preview_url": "/api/logos/preview/1/ocean_blue",
    "file_size": 52341
  }
}
```

**Errors:**
- 404: Logo not found
- 422: Invalid color scheme
- 403: Access denied (wrong session)

### GET /api/logos/download/{logo_id}

**Purpose:** Downloads a specific logo file (original or color-customized)
**Parameters:** 
- `logo_id` (integer, path): The ID of the logo to download
- `format` (string, query): File format ('png' or 'svg'), defaults to 'png'
- `color_scheme` (string, query, optional): Color scheme for customized version

**Response:** Binary file download with appropriate headers
```
Content-Type: image/png or image/svg+xml
Content-Disposition: attachment; filename="techflow-minimalist-1-ocean_blue.png"
```

**Errors:**
- 404: Logo not found
- 403: Access denied (wrong session)

### POST /api/logos/download/batch

**Purpose:** Creates a ZIP file containing multiple selected logos
**Parameters:**
- `logo_ids` (array, required): Array of logo IDs to include in ZIP
- `format` (string, optional): File format for all logos ('png' or 'svg'), defaults to 'png'

**Response:** Binary ZIP file download
```
Content-Type: application/zip
Content-Disposition: attachment; filename="techflow-logos.zip"
```

**Errors:**
- 422: Invalid logo_ids array
- 404: One or more logos not found
- 403: Access denied (wrong session)

## Controllers

### LogoGenerationController

**Actions:**
- `generate()` - Handles POST /api/logos/generate, validates input and dispatches generation job
- `status()` - Handles GET /api/logos/status/{generation_id}, returns current generation status
- `show()` - Handles GET /api/logos/{generation_id}, returns completed logos

**Business Logic:**
- Validates session ownership of generation requests
- Implements rate limiting (5 requests per hour per session)
- Queues logo generation jobs for background processing
- Handles API cost tracking and budget limits

### LogoDownloadController

**Actions:**
- `download()` - Handles GET /api/logos/download/{logo_id}, streams logo file
- `batchDownload()` - Handles POST /api/logos/download/batch, creates and streams ZIP

**Business Logic:**
- Validates file existence and access permissions
- Sets appropriate HTTP headers for file downloads
- Implements download rate limiting to prevent abuse
- Handles temporary ZIP file creation and cleanup

## Job Classes

### GenerateLogosJob

**Purpose:** Background job that handles the actual AI API integration and logo generation
**Queue:** 'logos' (dedicated queue for logo generation)
**Timeout:** 300 seconds (5 minutes)

**Process:**
1. Retrieve generation request and validate status
2. Generate prompts for each requested style and variation
3. Make API calls to DALL-E 3 for image generation
4. Download and store generated images locally
5. Update database with completion status and file paths
6. Handle failures with retry logic and error reporting

## Rate Limiting

- Logo generation: 5 requests per hour per session
- Status checks: 60 requests per minute per session
- Downloads: 30 requests per minute per session
- Batch downloads: 5 requests per hour per session